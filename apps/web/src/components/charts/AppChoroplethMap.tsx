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
const EXIT_MS = 400;

export interface AppChoroplethMapProps {
  geo: FeatureCollection<Geometry, { NUTS_ID: string }>;
  aggregates: RegionAggregate[];
  onSelectRegion?: (code: string) => void;
  /** Fired the instant a region is clicked (before the animation) so the caller can warm that
   *  region's feed into cache while the transition plays. */
  onRegionPrefetch?: (code: string) => void;
}

/** Bulgaria-oblasti choropleth: each province shaded by its flag count (darker red = more).
 *  Rendered to a viewBox SVG via d3-geo (no container measurement, so it's resize-safe and
 *  test-friendly). Hover shows the region + count. Clicking a region gently grows it in place
 *  (keeping its own shade) while the rest dim, then the map softly fades into the region's feed.
 *  Themed for light + dark, with high-contrast borders. */
export function AppChoroplethMap({
  geo,
  aggregates,
  onSelectRegion,
  onRegionPrefetch,
}: AppChoroplethMapProps) {
  const theme = useTheme();
  const { t } = useTranslation();
  const [hovered, setHovered] = useState<string | null>(null);
  const [exiting, setExiting] = useState<string | null>(null);

  const byCode = useMemo(() => {
    const map = new Map<string, RegionAggregate>();
    for (const a of aggregates) map.set(a.region_code, a);
    return map;
  }, [aggregates]);

  const max = useMemo(() => aggregates.reduce((m, a) => Math.max(m, a.metric), 0), [aggregates]);
  const pathGen = useMemo(() => geoPath(geoMercator().fitSize([WIDTH, HEIGHT], geo)), [geo]);

  // Borders: clearly stronger than the faint theme divider — especially in light mode.
  const borderColor =
    theme.palette.mode === 'dark' ? 'rgba(244, 241, 234, 0.40)' : 'rgba(10, 10, 10, 0.60)';

  function fillFor(code: string): string {
    const agg = byCode.get(code);
    if (agg === undefined || max === 0 || agg.metric === 0) return theme.palette.action.hover;
    const pct = Math.round((0.2 + 0.7 * (agg.metric / max)) * 100);
    return `color-mix(in srgb, ${palette.alarm} ${pct}%, transparent)`;
  }

  function handleClick(code: string): void {
    if (onSelectRegion === undefined || exiting !== null) return;
    onRegionPrefetch?.(code); // warm the region's feed while the animation plays
    setExiting(code);
    window.setTimeout(() => onSelectRegion(code), EXIT_MS);
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
        sx={{
          width: '100%',
          height: 'auto',
          display: 'block',
          overflow: 'visible',
          // Grow the region, then fade the map out — navigation fires right as the fade lands.
          opacity: exiting !== null ? 0 : 1,
          transition: 'opacity 260ms ease 120ms',
        }}
        role="img"
        aria-label={t('viz:map.heading')}
      >
        {geo.features.map((f) => {
          const code = f.properties.NUTS_ID;
          const d = pathGen(f);
          if (d === null) return null;
          const isHovered = hovered === code;
          const isExiting = exiting === code;
          const dimmed = exiting !== null && !isExiting;
          return (
            <path
              key={code}
              d={d}
              fill={fillFor(code)}
              stroke={isHovered || isExiting ? palette.alarm : borderColor}
              strokeWidth={isExiting ? 2 : isHovered ? 1.6 : 0.75}
              style={{
                cursor: onSelectRegion !== undefined ? 'pointer' : 'default',
                transformBox: 'fill-box',
                transformOrigin: 'center',
                transform: isExiting ? 'scale(1.5)' : 'scale(1)',
                opacity: dimmed ? 0.25 : 1,
                transition: 'transform 520ms cubic-bezier(0.33, 1, 0.68, 1), opacity 240ms ease, fill 120ms',
                pointerEvents: exiting !== null ? 'none' : 'auto',
              }}
              onMouseEnter={() => setHovered(code)}
              onMouseLeave={() => setHovered(null)}
              onClick={() => handleClick(code)}
            />
          );
        })}
      </Box>
    </Box>
  );
}
