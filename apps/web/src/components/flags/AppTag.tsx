import { Chip, type ChipProps } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { punkTagMeta } from '@/lib/tags';
import type { PunkTag } from '@/types/api';

export interface AppTagProps {
  tag: PunkTag;
  size?: ChipProps['size'];
}

/** Editorial punk tag (крадене на пари / кофти сделки / шуши-муши — CLAUDE.md §1.0.1).
 *  Filled in the alarm/secondary palette so it reads as the loud roast on top of the neutral
 *  sector/type chips. Colours come from the theme, never hardcoded. */
export function AppTag({ tag, size = 'small' }: AppTagProps) {
  const { t } = useTranslation();
  const meta = punkTagMeta[tag];
  const IconComponent = meta.icon;
  return (
    <Chip
      size={size}
      color="secondary"
      variant="filled"
      icon={<IconComponent size={14} weight="fill" />}
      label={t(meta.i18nKey)}
    />
  );
}
