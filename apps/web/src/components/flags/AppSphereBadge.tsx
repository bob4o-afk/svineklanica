import { Chip } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { sphereMeta } from '@/lib/spheres';
import type { Sphere } from '@/types/api';

export interface AppSphereBadgeProps {
  sphere: Sphere;
  size?: 'small' | 'medium';
}

/** Sphere badge (съдебна система / здравеопазване / полиция / образование) — the top level of the
 *  Sphere → Category → severity hierarchy (CLAUDE.md §1.0). Labels via i18n, never hardcoded. */
export function AppSphereBadge({ sphere, size = 'small' }: AppSphereBadgeProps) {
  const { t } = useTranslation();
  const meta = sphereMeta[sphere];
  // Degrade gracefully on an unknown value (e.g. a sphere the backend adds before the
  // frontend union catches up) — skip the badge rather than crash the whole feed card.
  if (meta === undefined) return null;
  const SphereIcon = meta.icon;

  return <Chip size={size} variant="outlined" icon={<SphereIcon size={14} weight="bold" />} label={t(meta.i18nKey)} />;
}
