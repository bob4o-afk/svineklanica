import { Chip, type ChipProps } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { flagTypeMeta } from '@/lib/flags';
import type { FlagType } from '@/types/api';

export interface AppFlagBadgeProps {
  type: FlagType;
  size?: ChipProps['size'];
}

/** Detector-type badge: the type's Phosphor icon + its Bulgarian label. */
export function AppFlagBadge({ type, size = 'small' }: AppFlagBadgeProps) {
  const { t } = useTranslation();
  const meta = flagTypeMeta[type];
  // Unknown detector type → skip the badge instead of crashing the card (graceful degradation).
  if (meta === undefined) return null;
  const IconComponent = meta.icon;
  return <Chip size={size} variant="outlined" icon={<IconComponent size={16} />} label={t(meta.i18nKey)} />;
}
