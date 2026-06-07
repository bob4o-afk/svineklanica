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
  /** The 0–100 suspicion score; when given it's shown next to the band, e.g. "Високо · 87%". */
  score?: number;
  size?: ChipProps['size'];
}

export function AppSeverityChip({ severity, score, size = 'small' }: AppSeverityChipProps) {
  const { t } = useTranslation();
  const meta = severityMeta[severity];
  const label = score !== undefined ? `${t(meta.i18nKey)} · ${score}%` : t(meta.i18nKey);
  return <Chip size={size} color={tokenToColor[meta.token]} label={label} variant="filled" />;
}
