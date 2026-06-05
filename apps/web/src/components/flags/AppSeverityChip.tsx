import { Chip, type ChipProps } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { severityMeta, type SeverityToken } from '@/lib/flags';
import type { FlagSeverity } from '@/types/api';

/** Severity token -> MUI palette slot. Colors themselves live only in the theme/tokens. */
const tokenToColor: Record<SeverityToken, NonNullable<ChipProps['color']>> = {
  alarm: 'error',
  rust: 'warning',
  acid: 'primary',
  muted: 'default',
};

export interface AppSeverityChipProps {
  severity: FlagSeverity;
  size?: ChipProps['size'];
}

export function AppSeverityChip({ severity, size = 'small' }: AppSeverityChipProps) {
  const { t } = useTranslation();
  const meta = severityMeta[severity];
  return <Chip size={size} color={tokenToColor[meta.token]} label={t(meta.i18nKey)} variant="filled" />;
}
