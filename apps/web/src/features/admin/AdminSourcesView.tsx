import { Chip, Stack, Typography } from '@mui/material';
import type { GridColDef } from '@mui/x-data-grid';
import { DatabaseIcon, PencilSimpleIcon, PlusIcon, TrashIcon } from '@phosphor-icons/react';
import { useCallback, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppIconButton } from '@/components/controls/AppIconButton';
import { AppSwitch } from '@/components/controls/AppSwitch';
import { AppTextField } from '@/components/controls/AppTextField';
import { AppDataGrid } from '@/components/data/AppDataGrid';
import { AppDialog } from '@/components/feedback/AppDialog';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import {
  type SourceFormValues,
  useCreateSource,
  useDeleteSource,
  useSources,
  useUpdateSource,
} from '@/hooks/queries/useSources';
import { formatDate } from '@/lib/date';
import { EMPTY_CELL } from '@/lib/format';
import { useToast } from '@/hooks/useToast';
import type { Source } from '@/types/api';

interface SourceFormState {
  key: string;
  label: string;
  base_url: string;
  enabled: boolean;
  notes: string;
}

function formFrom(source: Source | null): SourceFormState {
  if (source === null) return { key: '', label: '', base_url: '', enabled: true, notes: '' };
  return {
    key: source.key,
    label: source.label,
    base_url: source.base_url,
    enabled: source.enabled,
    notes: source.notes ?? '',
  };
}

function isHttpUrl(value: string): boolean {
  try {
    const url = new URL(value);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch {
    return false;
  }
}

function toValues(form: SourceFormState): SourceFormValues {
  return {
    key: form.key.trim(),
    label: form.label.trim(),
    base_url: form.base_url.trim(),
    enabled: form.enabled,
    ...(form.notes.trim() !== '' ? { notes: form.notes.trim() } : {}),
  };
}

/** Create/edit form inside the modal. Conditionally rendered by the parent, so it remounts (and
 *  re-initialises from `initial`) each time it opens — no effect-sync needed. */
function SourceDialog({
  initial,
  onClose,
  onSubmit,
  pending,
}: {
  initial: Source | null;
  onClose: () => void;
  onSubmit: (values: SourceFormValues) => void;
  pending: boolean;
}) {
  const { t } = useTranslation();
  const [form, setForm] = useState<SourceFormState>(() => formFrom(initial));

  const valid = form.key.trim() !== '' && form.label.trim() !== '' && isHttpUrl(form.base_url.trim());
  const title = initial === null ? t('admin:sources.add') : t('admin:sources.edit');

  return (
    <AppDialog
      open
      title={title}
      onClose={onClose}
      actions={
        <>
          <AppButton variant="text" onClick={onClose} disabled={pending}>
            {t('common:actions.cancel')}
          </AppButton>
          <AppButton onClick={() => onSubmit(toValues(form))} disabled={!valid || pending}>
            {t('common:actions.save')}
          </AppButton>
        </>
      }
    >
      <Stack spacing={2} sx={{ pt: 1 }}>
        <AppTextField
          label={t('admin:sources.form.key')}
          value={form.key}
          onChange={(event) => setForm((f) => ({ ...f, key: event.target.value }))}
          required
        />
        <AppTextField
          label={t('admin:sources.form.label')}
          value={form.label}
          onChange={(event) => setForm((f) => ({ ...f, label: event.target.value }))}
          required
        />
        <AppTextField
          label={t('admin:sources.form.baseUrl')}
          value={form.base_url}
          onChange={(event) => setForm((f) => ({ ...f, base_url: event.target.value }))}
          required
        />
        <AppSwitch
          label={t('admin:sources.form.enabled')}
          checked={form.enabled}
          onChange={(checked) => setForm((f) => ({ ...f, enabled: checked }))}
        />
        <AppTextField
          label={t('admin:sources.form.notes')}
          value={form.notes}
          onChange={(event) => setForm((f) => ({ ...f, notes: event.target.value }))}
          multiline
          minRows={2}
        />
      </Stack>
    </AppDialog>
  );
}

/** The data-source registry: list, add, edit, toggle active, delete. Mirrors SOURCES.md. */
export function AdminSourcesView() {
  const { t } = useTranslation();
  const { showToast } = useToast();
  const sources = useSources();
  const create = useCreateSource();
  const update = useUpdateSource();
  const remove = useDeleteSource();

  const [editing, setEditing] = useState<Source | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Source | null>(null);

  function openCreate() {
    setEditing(null);
    setDialogOpen(true);
  }

  const openEdit = useCallback((source: Source) => {
    setEditing(source);
    setDialogOpen(true);
  }, []);

  function closeDialog() {
    setDialogOpen(false);
    setEditing(null);
  }

  function submitForm(values: SourceFormValues) {
    if (editing !== null) {
      update.mutate(
        { publicId: editing.public_id, patch: values },
        {
          onSuccess: () => {
            showToast(t('admin:sources.updated'), 'success');
            closeDialog();
          },
          onError: () => showToast(t('admin:sources.error'), 'error'),
        },
      );
    } else {
      create.mutate(values, {
        onSuccess: () => {
          showToast(t('admin:sources.created'), 'success');
          closeDialog();
        },
        onError: () => showToast(t('admin:sources.error'), 'error'),
      });
    }
  }

  const toggleEnabled = useCallback(
    (source: Source) => {
      update.mutate(
        { publicId: source.public_id, patch: { enabled: !source.enabled } },
        { onError: () => showToast(t('admin:sources.error'), 'error') },
      );
    },
    [update, showToast, t],
  );

  function confirmDelete() {
    if (deleteTarget === null) return;
    remove.mutate(deleteTarget.public_id, {
      onSuccess: () => {
        showToast(t('admin:sources.deleted'), 'info');
        setDeleteTarget(null);
      },
      onError: () => showToast(t('admin:sources.error'), 'error'),
    });
  }

  const columns = useMemo<GridColDef<Source>[]>(
    () => [
      { field: 'label', headerName: t('admin:sources.columns.label'), flex: 1, minWidth: 180 },
      { field: 'key', headerName: t('admin:sources.columns.key'), width: 100 },
      { field: 'base_url', headerName: t('admin:sources.columns.baseUrl'), flex: 1, minWidth: 180 },
      {
        field: 'enabled',
        headerName: t('admin:sources.columns.enabled'),
        width: 120,
        sortable: false,
        renderCell: (params) => (
          <Chip
            clickable
            size="small"
            onClick={() => toggleEnabled(params.row)}
            color={params.row.enabled ? 'success' : 'default'}
            variant={params.row.enabled ? 'filled' : 'outlined'}
            label={t(params.row.enabled ? 'admin:sources.active' : 'admin:sources.inactive')}
          />
        ),
      },
      {
        field: 'last_ingested_at',
        headerName: t('admin:sources.columns.lastIngested'),
        width: 130,
        renderCell: (params) =>
          params.row.last_ingested_at !== undefined ? formatDate(params.row.last_ingested_at) : EMPTY_CELL,
      },
      {
        field: 'actions',
        headerName: '',
        width: 100,
        sortable: false,
        filterable: false,
        renderCell: (params) => (
          <Stack direction="row" spacing={0.5}>
            <AppIconButton size="small" label={t('common:actions.edit')} onClick={() => openEdit(params.row)}>
              <PencilSimpleIcon size={18} />
            </AppIconButton>
            <AppIconButton
              size="small"
              label={t('common:actions.delete')}
              onClick={() => setDeleteTarget(params.row)}
            >
              <TrashIcon size={18} />
            </AppIconButton>
          </Stack>
        ),
      },
    ],
    [t, openEdit, toggleEnabled],
  );

  return (
    <Stack spacing={2}>
      <Stack direction="row" alignItems="center" justifyContent="space-between" spacing={1}>
        <Typography variant="h4" component="h1">
          {t('admin:sources.title')}
        </Typography>
        <AppButton startIcon={<PlusIcon />} onClick={openCreate} size="small">
          {t('admin:sources.add')}
        </AppButton>
      </Stack>

      {sources.isPending ? (
        <AppSkeleton count={3} />
      ) : sources.isError ? (
        <AppErrorState error={sources.error} onRetry={() => void sources.refetch()} />
      ) : sources.data.length === 0 ? (
        <AppEmptyState icon={DatabaseIcon} title={t('admin:sources.empty')} />
      ) : (
        <AppDataGrid
          rows={sources.data}
          columns={columns}
          getRowId={(row) => row.public_id}
          ariaLabel={t('admin:sources.title')}
        />
      )}

      {dialogOpen ? (
        <SourceDialog
          initial={editing}
          onClose={closeDialog}
          onSubmit={submitForm}
          pending={create.isPending || update.isPending}
        />
      ) : null}

      <AppDialog
        open={deleteTarget !== null}
        title={t('admin:sources.title')}
        onClose={() => setDeleteTarget(null)}
        actions={
          <>
            <AppButton variant="text" onClick={() => setDeleteTarget(null)} disabled={remove.isPending}>
              {t('common:actions.cancel')}
            </AppButton>
            <AppButton color="error" onClick={confirmDelete} disabled={remove.isPending}>
              {t('common:actions.delete')}
            </AppButton>
          </>
        }
      >
        <Typography variant="body2">
          {t('admin:sources.deleteConfirm', { label: deleteTarget?.label ?? '' })}
        </Typography>
      </AppDialog>
    </Stack>
  );
}
