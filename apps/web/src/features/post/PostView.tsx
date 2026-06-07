import { Chip, Divider, Stack, Typography } from '@mui/material';
import { ArrowLeftIcon, ChartLineIcon, EyeIcon, GraphIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { Link as RouterLink } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppSeo } from '@/components/layout/AppSeo';
import { AppCategoryBadge } from '@/components/flags/AppCategoryBadge';
import { AppEvidenceList } from '@/components/flags/AppEvidenceList';
import { AppFlagBadge } from '@/components/flags/AppFlagBadge';
import { AppSectorBadge } from '@/components/flags/AppSectorBadge';
import { AppSeverityChip } from '@/components/flags/AppSeverityChip';
import { AppSourceLink } from '@/components/flags/AppSourceLink';
import { AppSphereBadge } from '@/components/flags/AppSphereBadge';
import { AppTag } from '@/components/flags/AppTag';
import { AppTldr } from '@/components/flags/AppTldr';
import { useFlagPost } from '@/hooks/queries/useFlagPost';
import { formatDate } from '@/lib/date';
import { formatNumber } from '@/lib/format';
import { flagTypeMeta, makeTldr } from '@/lib/flags';
import { paths } from '@/routes/paths';

export interface PostViewProps {
  publicId: string;
}

/** Full flag-post detail: headline, neutral explanation, the numbers, and every primary
 *  source — each link scheme-validated by AppSourceLink. */
export function PostView({ publicId }: PostViewProps) {
  const { t } = useTranslation();
  const post = useFlagPost(publicId);

  if (post.isPending) return <AppSkeleton count={3} />;
  if (post.isError) {
    return (
      <AppErrorState
        title={t('post:notFound.title')}
        message={t('post:notFound.body')}
        error={post.error}
        onRetry={() => void post.refetch()}
      />
    );
  }

  const flag = post.data;
  const headline = flag.title ?? t(flagTypeMeta[flag.type].i18nKey);

  return (
    <Stack spacing={3}>
      <AppSeo title={headline} description={flag.explanation_bg} />
      <AppButton variant="text" to={paths.feed} startIcon={<ArrowLeftIcon />} sx={{ alignSelf: 'flex-start' }}>
        {t('common:actions.back')}
      </AppButton>

      {/* Sphere → Category → severity (+score %) — the §1.0 hierarchy, then type/sector/tags. */}
      <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
        {flag.sphere !== undefined ? <AppSphereBadge sphere={flag.sphere} /> : null}
        {flag.corruption_category !== undefined ? (
          <AppCategoryBadge category={flag.corruption_category} />
        ) : null}
        <AppSeverityChip severity={flag.severity} {...(flag.score !== undefined ? { score: flag.score } : {})} />
        <AppFlagBadge type={flag.type} />
        {flag.category !== undefined ? <AppSectorBadge sector={flag.category} /> : null}
        {(flag.tags ?? []).map((tag) => (
          <AppTag key={tag} tag={tag} />
        ))}
      </Stack>

      <Typography variant="h4" component="h1">
        {headline}
      </Typography>

      <AppTldr text={makeTldr(flag)} />

      {flag.series_key !== undefined ? (
        <AppButton
          variant="outlined"
          to={paths.price(flag.series_key)}
          startIcon={<ChartLineIcon />}
          sx={{ alignSelf: 'flex-start' }}
        >
          {t('viz:price.viewLink')}
        </AppButton>
      ) : null}

      {flag.type === 'serial_winner' && flag.subject.company !== undefined ? (
        <AppButton
          variant="outlined"
          to={paths.network(flag.subject.company.public_id)}
          startIcon={<GraphIcon />}
          sx={{ alignSelf: 'flex-start' }}
        >
          {t('viz:network.viewLink')}
        </AppButton>
      ) : null}

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('post:explanation')}
        </Typography>
        <Typography variant="body1">{flag.explanation_bg}</Typography>
      </section>

      <Divider />

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('post:subject')}
        </Typography>
        <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
          {flag.subject.authority !== undefined ? (
            <Chip
              component={RouterLink}
              to={paths.authority(flag.subject.authority.public_id)}
              clickable
              variant="outlined"
              label={flag.subject.authority.name}
            />
          ) : null}
          {flag.subject.company !== undefined ? (
            <Chip
              component={RouterLink}
              to={paths.company(flag.subject.company.eik)}
              clickable
              variant="outlined"
              label={flag.subject.company.name}
            />
          ) : null}
          {flag.subject.tender !== undefined ? (
            <Chip variant="outlined" label={flag.subject.tender.title} />
          ) : null}
        </Stack>
      </section>

      <Divider />

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('post:evidence')}
        </Typography>
        <AppEvidenceList items={flag.evidence} />
      </section>

      <Divider />

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('post:sources')}
        </Typography>
        <Stack spacing={1}>
          {flag.sources.map((source, index) => (
            <AppSourceLink key={`${source.url}-${index}`} source={source} />
          ))}
        </Stack>
      </section>

      <Stack direction="row" spacing={1.5} alignItems="center" sx={{ color: 'text.secondary' }}>
        <Stack
          direction="row"
          spacing={0.5}
          alignItems="center"
          aria-label={t('flags:card.views')}
          title={t('flags:card.views')}
        >
          <EyeIcon size={16} />
          <Typography variant="caption">{formatNumber(flag.view_count ?? 0)}</Typography>
        </Stack>
        <Typography variant="caption">
          {t('flags:card.detectedAt')}: {formatDate(flag.detected_at)}
        </Typography>
      </Stack>
    </Stack>
  );
}
