import { Paper, Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { formatNumber } from '@/lib/format';
import { formatMoney } from '@/lib/money';
import { fonts } from '@/theme/typography';
import type { EntityStats } from '@/types/api';

export interface AppEntityStatsProps {
  stats: EntityStats;
}

/** Headline numbers for an entity (authority/company): flag count, critical count, and total
 *  contracted value when the API provides it. */
export function AppEntityStats({ stats }: AppEntityStatsProps) {
  const { t } = useTranslation();
  const tiles: { label: string; value: string }[] = [
    { label: t('entity:stats.flags'), value: formatNumber(stats.flag_count) },
    { label: t('entity:stats.critical'), value: formatNumber(stats.critical_count) },
    ...(stats.total_value !== undefined
      ? [{ label: t('entity:stats.value'), value: formatMoney(stats.total_value) }]
      : []),
  ];

  return (
    <Stack direction="row" spacing={2} flexWrap="wrap" useFlexGap>
      {tiles.map((tile) => (
        <Paper key={tile.label} variant="outlined" sx={{ px: 2, py: 1.5, flex: 1, minWidth: 120 }}>
          <Typography variant="overline" color="text.secondary" display="block">
            {tile.label}
          </Typography>
          <Typography variant="h5" component="p" sx={{ fontFamily: fonts.mono }}>
            {tile.value}
          </Typography>
        </Paper>
      ))}
    </Stack>
  );
}
