import type { GridColDef } from '@mui/x-data-grid';
import { useTranslation } from 'react-i18next';
import { AppFlagBadge } from '@/components/flags/AppFlagBadge';
import { AppSeverityChip } from '@/components/flags/AppSeverityChip';
import { formatDate } from '@/lib/date';
import { EMPTY_CELL } from '@/lib/format';
import { flagTypeMeta, severityRank } from '@/lib/flags';
import { formatMoneyShort } from '@/lib/money';
import type { FlagPost, MoneyAmount } from '@/types/api';

function topMoney(flag: FlagPost): MoneyAmount | undefined {
  return flag.evidence.find((e) => e.money !== undefined)?.money;
}

/** Columns for a DataGrid of an entity's flag history. Severity sorts by rank, value sorts by
 *  amount; cells render the shared chips/formatters so the grid matches the rest of the UI. */
export function useFlagColumns(): GridColDef<FlagPost>[] {
  const { t } = useTranslation();
  return [
    {
      field: 'severity',
      headerName: t('entity:columns.severity'),
      width: 120,
      valueGetter: (_value, row) => severityRank(row.severity),
      renderCell: (params) => <AppSeverityChip severity={params.row.severity} />,
    },
    {
      field: 'type',
      headerName: t('entity:columns.type'),
      width: 170,
      valueGetter: (_value, row) => t(flagTypeMeta[row.type].i18nKey),
      renderCell: (params) => <AppFlagBadge type={params.row.type} />,
    },
    {
      field: 'title',
      headerName: t('entity:columns.title'),
      flex: 1,
      minWidth: 220,
      valueGetter: (_value, row) => row.title ?? t(flagTypeMeta[row.type].i18nKey),
    },
    {
      field: 'value',
      headerName: t('entity:columns.value'),
      width: 130,
      align: 'right',
      headerAlign: 'right',
      valueGetter: (_value, row) => topMoney(row)?.amount ?? 0,
      renderCell: (params) => {
        const money = topMoney(params.row);
        return money !== undefined ? formatMoneyShort(money) : EMPTY_CELL;
      },
    },
    {
      field: 'detected_at',
      headerName: t('entity:columns.date'),
      width: 120,
      valueGetter: (_value, row) => row.detected_at,
      renderCell: (params) => formatDate(params.row.detected_at),
    },
  ];
}
