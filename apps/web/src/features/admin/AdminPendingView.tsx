import { Stack, Typography } from '@mui/material';
import { ClipboardTextIcon } from '@phosphor-icons/react';
import type { GridColDef } from '@mui/x-data-grid';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppDataGrid } from '@/components/data/AppDataGrid';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppFlagBadge } from '@/components/flags/AppFlagBadge';
import { AppSeverityChip } from '@/components/flags/AppSeverityChip';
import { usePendingFlags } from '@/hooks/queries/usePendingFlags';
import { formatDate } from '@/lib/date';
import { flagTypeMeta } from '@/lib/flags';
import { paths } from '@/routes/paths';
import type { FlagPost } from '@/types/api';

function subjectLabel(flag: FlagPost): string {
  const { authority, company, tender } = flag.subject;
  return authority?.name ?? company?.name ?? tender?.title ?? '';
}

/** The review queue as a grid — newest first; click a row to open the review panel. */
export function AdminPendingView() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const pending = usePendingFlags();

  const columns = useMemo<GridColDef<FlagPost>[]>(
    () => [
      {
        field: 'detected_at',
        headerName: t('admin:pending.columns.detectedAt'),
        width: 120,
        renderCell: (params) => formatDate(params.row.detected_at),
      },
      {
        field: 'type',
        headerName: t('admin:pending.columns.type'),
        width: 200,
        sortable: false,
        renderCell: (params) => <AppFlagBadge type={params.row.type} />,
        valueGetter: (_value, row) => t(flagTypeMeta[row.type].i18nKey),
      },
      {
        field: 'severity',
        headerName: t('admin:pending.columns.severity'),
        width: 130,
        renderCell: (params) => <AppSeverityChip severity={params.row.severity} />,
      },
      {
        field: 'subject',
        headerName: t('admin:pending.columns.subject'),
        flex: 1,
        minWidth: 200,
        sortable: false,
        valueGetter: (_value, row) => subjectLabel(row),
      },
      {
        field: 'sources',
        headerName: t('admin:pending.columns.sources'),
        width: 110,
        sortable: false,
        renderCell: (params) => params.row.sources.length,
      },
    ],
    [t],
  );

  if (pending.isPending) return <AppSkeleton count={3} />;
  if (pending.isError) {
    return <AppErrorState error={pending.error} onRetry={() => void pending.refetch()} />;
  }

  const rows = pending.data.data;

  return (
    <Stack spacing={2}>
      <Typography variant="h4" component="h1">
        {t('admin:pending.title')}
      </Typography>
      {rows.length === 0 ? (
        <AppEmptyState icon={ClipboardTextIcon} title={t('admin:pending.empty')} />
      ) : (
        <AppDataGrid
          rows={rows}
          columns={columns}
          getRowId={(row) => row.public_id}
          onRowClick={(row) => navigate(paths.adminReview(row.public_id))}
          ariaLabel={t('admin:pending.title')}
        />
      )}
    </Stack>
  );
}
