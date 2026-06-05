import { Stack, Typography } from '@mui/material';
import { ArrowLeftIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppDataGrid } from '@/components/data/AppDataGrid';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppEntityStats } from '@/components/flags/AppEntityStats';
import { AppSeo } from '@/components/layout/AppSeo';
import { useFlagColumns } from '@/features/entity/useFlagColumns';
import { useAuthority } from '@/hooks/queries/useAuthority';
import { paths } from '@/routes/paths';

export interface AuthorityViewProps {
  publicId: string;
}

/** A contracting authority's profile: name + region, headline stats, and its flag history in a
 *  sortable grid (row → the flag's post). */
export function AuthorityView({ publicId }: AuthorityViewProps) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const columns = useFlagColumns();
  const query = useAuthority(publicId);

  if (query.isPending) return <AppSkeleton count={3} />;
  if (query.isError) {
    return (
      <AppErrorState
        title={t('entity:notFound.title')}
        message={t('entity:notFound.body')}
        error={query.error}
        onRetry={() => void query.refetch()}
      />
    );
  }

  const { authority, stats, flags } = query.data;

  return (
    <Stack spacing={3}>
      <AppSeo title={authority.name} />
      <AppButton variant="text" to={paths.feed} startIcon={<ArrowLeftIcon />} sx={{ alignSelf: 'flex-start' }}>
        {t('common:actions.back')}
      </AppButton>

      <Stack spacing={0.5}>
        <Typography variant="overline" color="text.secondary">
          {t('flags:subject.authority')}
        </Typography>
        <Typography variant="h4" component="h1">
          {authority.name}
        </Typography>
        {authority.region_code !== undefined ? (
          <Typography variant="body2" color="text.secondary">
            {t('entity:region')}: {authority.region_code}
          </Typography>
        ) : null}
      </Stack>

      <AppEntityStats stats={stats} />

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('entity:flags.title')}
        </Typography>
        {flags.data.length > 0 ? (
          <AppDataGrid
            rows={flags.data}
            columns={columns}
            getRowId={(flag) => flag.public_id}
            onRowClick={(flag) => navigate(paths.post(flag.public_id))}
            ariaLabel={t('entity:flags.title')}
          />
        ) : (
          <AppEmptyState title={t('entity:flags.empty')} />
        )}
      </section>
    </Stack>
  );
}
