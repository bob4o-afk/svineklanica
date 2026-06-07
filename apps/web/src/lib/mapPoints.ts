import { palette } from '@/theme/tokens';
import type { FlagSeverity, FlagType } from '@/types/api';

/** A single flag pinned on the map at its region's centroid (location derived from the flag's
 *  authority region — CLAUDE.md §1.2 / data-sources.md geo). Mirrors `GET /api/map/flag-points`. */
export interface FlagMapPoint {
  public_id: string;
  region_code: string;
  severity: FlagSeverity;
  type: FlagType;
  title?: string;
}

/** Severity → marker/dot colour, from the theme tokens (no hardcoded hex elsewhere — frontend.md §1). */
export const SEVERITY_COLOR: Record<FlagSeverity, string> = {
  low: palette.muted,
  medium: palette.rust,
  high: palette.alarm,
  critical: palette.alarm,
};
