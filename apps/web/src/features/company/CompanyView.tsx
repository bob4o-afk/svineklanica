import { Chip, Stack, Typography } from '@mui/material';
import { ArrowLeftIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppDataGrid } from '@/components/data/AppDataGrid';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppEntityStats } from '@/components/flags/AppEntityStats';
import { AppSeo } from '@/components/layout/AppSeo';
import { useFlagColumns } from '@/features/entity/useFlagColumns';
import { useCompany } from '@/hooks/queries/useCompany';
import { formatEik } from '@/lib/format';
import { paths } from '@/routes/paths';

export interface CompanyViewProps {
  eik: string;
}

/** A company's profile (by EIK): stats, its flag history (sortable grid → post), and related
 *  (shell-cluster candidate) companies linking to their own profiles. */
export function CompanyView({ eik }: CompanyViewProps) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const columns = useFlagColumns();
  const query = useCompany(eik);

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

  const { company, stats, flags, related } = query.data;

  return (
    <Stack spacing={3}>
      <AppSeo title={company.name} />
      <AppButton variant="text" to={paths.feed} startIcon={<ArrowLeftIcon />} sx={{ alignSelf: 'flex-start' }}>
        {t('common:actions.back')}
      </AppButton>

      <Stack spacing={0.5}>
        <Typography variant="overline" color="text.secondary">
          {t('flags:subject.company')}
        </Typography>
        <Typography variant="h4" component="h1">
          {company.name}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          {t('entity:eik')}: {formatEik(company.eik)}
        </Typography>
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

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('entity:related.title')}
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
          {t('entity:related.hint')}
        </Typography>
        {related.length > 0 ? (
          <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
            {related.map((c) => (
              <Chip
                key={c.public_id}
                component={RouterLink}
                to={paths.company(c.eik)}
                clickable
                variant="outlined"
                label={c.name}
              />
            ))}
          </Stack>
        ) : (
          <Typography variant="body2" color="text.secondary">
            {t('entity:related.empty')}
          </Typography>
        )}
      </section>
    </Stack>
  );
}
