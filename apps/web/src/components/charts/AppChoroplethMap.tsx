import { Box, Typography } from '@mui/material';
import { useTheme } from '@mui/material/styles';
import { geoMercator, geoPath } from 'd3-geo';
import type { FeatureCollection, Geometry } from 'geojson';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { regionName } from '@/lib/regions';
import { palette } from '@/theme/tokens';
import type { RegionAggregate } from '@/types/api';

const WIDTH = 800;
const HEIGHT = 520;

export interface AppChoroplethMapProps {
  geo: FeatureCollection<Geometry, { NUTS_ID: string }>;
  aggregates: RegionAggregate[];
  onSelectRegion?: (code: string) => void;
}

/** Bulgaria-oblasti choropleth: each province shaded by its flag count (darker red = more).
 *  Rendered to a viewBox SVG via d3-geo (no container measurement, so it's resize-safe and
 *  test-friendly). Hover shows the region + count; click drills into that region's feed. */
export function AppChoroplethMap({ geo, aggregates, onSelectRegion }: AppChoroplethMapProps) {
  const theme = useTheme();
  const { t } = useTranslation();
  const [hovered, setHovered] = useState<string | null>(null);

  const byCode = useMemo(() => {
    const map = new Map<string, RegionAggregate>();
    for (const a of aggregates) map.set(a.region_code, a);
    return map;
  }, [aggregates]);

  const max = useMemo(() => aggregates.reduce((m, a) => Math.max(m, a.metric), 0), [aggregates]);

  const pathGen = useMemo(() => geoPath(geoMercator().fitSize([WIDTH, HEIGHT], geo)), [geo]);

  function fillFor(code: string): string {
    const agg = byCode.get(code);
    if (agg === undefined || max === 0 || agg.metric === 0) return theme.palette.action.hover;
    const pct = Math.round((0.2 + 0.7 * (agg.metric / max)) * 100);
    return `color-mix(in srgb, ${palette.alarm} ${pct}%, transparent)`;
  }

  const hoveredAgg = hovered !== null ? byCode.get(hovered) : undefined;

  return (
    <Box>
      <Box sx={{ minHeight: 28, mb: 1 }}>
        {hovered !== null ? (
          <Typography variant="body2">
            <strong>{regionName(hovered)}</strong>
            {hoveredAgg !== undefined
              ? ` · ${t('viz:map.flagCount', { count: hoveredAgg.flag_count })}`
              : ` · ${t('viz:map.noData')}`}
          </Typography>
        ) : (
          <Typography variant="body2" color="text.secondary">
            {t('viz:map.hint')}
          </Typography>
        )}
      </Box>
      <Box
        component="svg"
        viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
        sx={{ width: '100%', height: 'auto', display: 'block' }}
        role="img"
        aria-label={t('viz:map.heading')}
      >
        {geo.features.map((f) => {
          const code = f.properties.NUTS_ID;
          const d = pathGen(f);
          if (d === null) return null;
          const isHovered = hovered === code;
          return (
            <path
              key={code}
              d={d}
              fill={fillFor(code)}
              stroke={isHovered ? palette.alarm : theme.palette.divider}
              strokeWidth={isHovered ? 1.5 : 0.5}
              style={{ cursor: onSelectRegion !== undefined ? 'pointer' : 'default', transition: 'fill 120ms' }}
              onMouseEnter={() => setHovered(code)}
              onMouseLeave={() => setHovered(null)}
              onClick={() => onSelectRegion?.(code)}
            />
          );
        })}
      </Box>
    </Box>
  );
}
