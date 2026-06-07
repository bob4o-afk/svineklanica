import { Box, Typography } from '@mui/material';
import { useTheme } from '@mui/material/styles';
import { geoMercator, geoPath } from 'd3-geo';
import type { FeatureCollection, Geometry } from 'geojson';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { flagTypeMeta } from '@/lib/flags';
import { type FlagMapPoint, SEVERITY_COLOR } from '@/lib/mapPoints';
import { regionName } from '@/lib/regions';
import { palette } from '@/theme/tokens';
import { fonts } from '@/theme/typography';
import type { FlagSeverity, FlagType, RegionAggregate } from '@/types/api';

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
  /** Individual flags to pin on the map (each at its region centroid, jittered when several
   *  share a region). Omit for a plain choropleth. */
  flagPoints?: FlagMapPoint[];
  /** Clicking a flag marker opens that post. */
  onSelectFlag?: (publicId: string) => void;
  /** Play the grow-then-fade exit on region click (for navigating away). Set false when the
   *  click keeps the user on this screen (e.g. mobile opens a sheet) so the map doesn't fade out.
   *  Default true. */
  animateRegionSelect?: boolean;
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
  flagPoints,
  onSelectFlag,
  animateRegionSelect = true,
}: AppChoroplethMapProps) {
  const theme = useTheme();
  const { t } = useTranslation();
  const [hovered, setHovered] = useState<string | null>(null);
  const [hoveredFlag, setHoveredFlag] = useState<string | null>(null);
  const [exiting, setExiting] = useState<string | null>(null);

  const byCode = useMemo(() => {
    const map = new Map<string, RegionAggregate>();
    for (const a of aggregates) map.set(a.region_code, a);
    return map;
  }, [aggregates]);

  const max = useMemo(() => aggregates.reduce((m, a) => Math.max(m, a.metric), 0), [aggregates]);
  const pathGen = useMemo(() => geoPath(geoMercator().fitSize([WIDTH, HEIGHT], geo)), [geo]);

  // Project each flag to its region centroid. When several flags share a region they're spread in
  // a small golden-angle spiral so they don't stack into a single dot; a flag whose region has no
  // polygon match isn't pinned (data-sources.md: no coords → no pin).
  const markers = useMemo(() => {
    if (flagPoints === undefined || flagPoints.length === 0) return [];
    const centroidByCode = new Map<string, [number, number]>();
    for (const f of geo.features) {
      const c = pathGen.centroid(f);
      if (Number.isFinite(c[0]) && Number.isFinite(c[1])) centroidByCode.set(f.properties.NUTS_ID, c);
    }
    const seen = new Map<string, number>();
    const out: Array<{
      id: string;
      severity: FlagSeverity;
      type: FlagType;
      regionCode: string;
      title?: string;
      x: number;
      y: number;
    }> = [];
    for (const fp of flagPoints) {
      const c = centroidByCode.get(fp.region_code);
      if (c === undefined) continue;
      const i = seen.get(fp.region_code) ?? 0;
      seen.set(fp.region_code, i + 1);
      const angle = i * 2.399963229728653; // golden angle (radians)
      const radius = i === 0 ? 0 : 5 + 3 * Math.sqrt(i);
      out.push({
        id: fp.public_id,
        severity: fp.severity,
        type: fp.type,
        regionCode: fp.region_code,
        ...(fp.title !== undefined ? { title: fp.title } : {}),
        x: c[0] + radius * Math.cos(angle),
        y: c[1] + radius * Math.sin(angle),
      });
    }
    return out;
  }, [flagPoints, geo, pathGen]);

  const activeFlag = hoveredFlag !== null ? markers.find((m) => m.id === hoveredFlag) : undefined;

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
    // The grow-then-fade exit is the page-transition flourish for navigating away. When the
    // selection stays on this screen (mobile opens a sheet), skip it — otherwise the map would
    // fade to nothing and never come back, since nothing unmounts it.
    if (!animateRegionSelect) {
      onSelectRegion(code);
      return;
    }
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

        {/* Flag pins — one dot per flag at its region centroid, coloured by severity. */}
        {markers.map((m) => (
          <circle
            key={m.id}
            cx={m.x}
            cy={m.y}
            r={hoveredFlag === m.id ? 7 : 5}
            fill={SEVERITY_COLOR[m.severity]}
            stroke={theme.palette.background.paper}
            strokeWidth={1.5}
            style={{
              cursor: onSelectFlag !== undefined ? 'pointer' : 'default',
              opacity: exiting !== null ? 0 : 0.95,
              transition: 'r 120ms ease, opacity 200ms ease',
              pointerEvents: exiting !== null ? 'none' : 'auto',
            }}
            onMouseEnter={() => setHoveredFlag(m.id)}
            onMouseLeave={() => setHoveredFlag((cur) => (cur === m.id ? null : cur))}
            onClick={() => onSelectFlag?.(m.id)}
          />
        ))}

        {/* Hover TL;DR — a red-on-black tooltip near the pin (CLAUDE.md punk look). Rendered last
            so it sits above every pin; SVG so it scales with the map and needs no DOM measuring. */}
        {activeFlag !== undefined && exiting === null
          ? (() => {
              const PAD = 12;
              const headerH = 18;
              const headerText = t(flagTypeMeta[activeFlag.type].i18nKey).toUpperCase();
              const regionText = regionName(activeFlag.regionCode);
              const rawTitle = activeFlag.title ?? '';
              const hasTitle = rawTitle !== '';
              const title = rawTitle.length > 42 ? `${rawTitle.slice(0, 41)}…` : rawTitle;
              // SVG text can't auto-size its box, so estimate each line's width from its length and
              // per-font glyph width (Cyrillic-safe factors) and size the box to the widest line —
              // so the text is always bounded by the box, never overflowing it.
              const wHeader = headerText.length * 6.4; // mono 10 + letter-spacing
              const wTitle = hasTitle ? title.length * 7.4 : 0; // display 12 bold
              const wRegion = regionText.length * 6.1; // mono 10
              const W = Math.min(
                Math.max(Math.ceil(Math.max(wHeader, wTitle, wRegion)) + PAD * 2, 132),
                WIDTH - 8,
              );
              const H = 14 + headerH + (hasTitle ? 18 : 0) + 16;
              const tx = Math.min(Math.max(activeFlag.x - W / 2, 4), WIDTH - W - 4);
              const above = activeFlag.y - H - 12 >= 0;
              const ty = above ? activeFlag.y - H - 12 : activeFlag.y + 12;
              return (
                <g style={{ pointerEvents: 'none' }}>
                  <rect
                    x={tx}
                    y={ty}
                    width={W}
                    height={H}
                    rx={3}
                    fill={palette.ink}
                    stroke={palette.alarm}
                    strokeWidth={1.5}
                    opacity={0.97}
                  />
                  <text
                    x={tx + 10}
                    y={ty + 16}
                    fill={palette.alarm}
                    style={{ fontFamily: fonts.mono, fontSize: 10, fontWeight: 700, letterSpacing: '0.08em' }}
                  >
                    {headerText}
                  </text>
                  {hasTitle ? (
                    <text
                      x={tx + 10}
                      y={ty + 16 + headerH}
                      fill={palette.bone}
                      style={{ fontFamily: fonts.display, fontSize: 12, fontWeight: 700 }}
                    >
                      {title}
                    </text>
                  ) : null}
                  <text
                    x={tx + 10}
                    y={ty + H - 10}
                    fill={palette.muted}
                    style={{ fontFamily: fonts.mono, fontSize: 10, letterSpacing: '0.04em' }}
                  >
                    {regionText}
                  </text>
                </g>
              );
            })()
          : null}
      </Box>
    </Box>
  );
}
