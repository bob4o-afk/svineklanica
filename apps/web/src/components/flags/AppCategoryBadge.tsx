import { Chip } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { corruptionCategoryMeta } from '@/lib/spheres';
import type { CorruptionCategory } from '@/types/api';

export interface AppCategoryBadgeProps {
  category: CorruptionCategory;
  size?: 'small' | 'medium';
}

/** Corruption-category badge (обществена поръчка / нерегламентирани плащания) — the abuse mechanism,
 *  the middle level of the Sphere → Category → severity hierarchy (CLAUDE.md §1.0). */
export function AppCategoryBadge({ category, size = 'small' }: AppCategoryBadgeProps) {
  const { t } = useTranslation();
  const meta = corruptionCategoryMeta[category];
  // Unknown category → skip the badge instead of crashing the card (graceful degradation).
  if (meta === undefined) return null;
  const CategoryIcon = meta.icon;

  return <Chip size={size} variant="outlined" icon={<CategoryIcon size={14} weight="bold" />} label={t(meta.i18nKey)} />;
}
