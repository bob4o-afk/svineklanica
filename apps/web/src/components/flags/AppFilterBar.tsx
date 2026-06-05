import { Button, Chip, Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { flagTypeMeta, severityMeta } from '@/lib/flags';
import type { FlagSeverity, FlagType } from '@/types/api';

const ALL_SEVERITIES: readonly FlagSeverity[] = ['critical', 'high', 'medium', 'low'];
const ALL_TYPES: readonly FlagType[] = [
  'price_discrepancy',
  'serial_winner',
  'tailored_spec',
  'cancelled',
  'implausible_scope',
  'delayed_payment',
  'doc_clone',
];

export interface AppFilterValue {
  type: FlagType[];
  severity: FlagSeverity[];
}

export interface AppFilterBarProps {
  value: AppFilterValue;
  onChange: (next: AppFilterValue) => void;
}

function toggle<T>(list: T[], item: T): T[] {
  return list.includes(item) ? list.filter((x) => x !== item) : [...list, item];
}

/** Faceted feed filters as toggle chips (severity + detector type). Domain values only; the
 *  caller persists them to the URL and feeds them to useFlagFeed. */
export function AppFilterBar({ value, onChange }: AppFilterBarProps) {
  const { t } = useTranslation();
  const hasAny = value.type.length > 0 || value.severity.length > 0;

  return (
    <Stack spacing={1}>
      <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap" useFlexGap>
        <Typography variant="overline" color="text.secondary">
          {t('feed:filter.severity')}
        </Typography>
        {ALL_SEVERITIES.map((s) => {
          const active = value.severity.includes(s);
          return (
            <Chip
              key={s}
              label={t(severityMeta[s].i18nKey)}
              size="small"
              color={active ? 'primary' : 'default'}
              variant={active ? 'filled' : 'outlined'}
              onClick={() => onChange({ ...value, severity: toggle(value.severity, s) })}
            />
          );
        })}
      </Stack>
      <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap" useFlexGap>
        <Typography variant="overline" color="text.secondary">
          {t('feed:filter.type')}
        </Typography>
        {ALL_TYPES.map((ft) => {
          const active = value.type.includes(ft);
          return (
            <Chip
              key={ft}
              label={t(flagTypeMeta[ft].i18nKey)}
              size="small"
              color={active ? 'primary' : 'default'}
              variant={active ? 'filled' : 'outlined'}
              onClick={() => onChange({ ...value, type: toggle(value.type, ft) })}
            />
          );
        })}
        {hasAny ? (
          <Button size="small" variant="text" onClick={() => onChange({ type: [], severity: [] })}>
            {t('feed:filter.clear')}
          </Button>
        ) : null}
      </Stack>
    </Stack>
  );
}
