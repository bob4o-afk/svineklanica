import { Divider, Stack, Typography } from '@mui/material';
import { ArrowLeftIcon } from '@phosphor-icons/react';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppSeo } from '@/components/layout/AppSeo';
import { AppEvidenceList } from '@/components/flags/AppEvidenceList';
import { AppFlagBadge } from '@/components/flags/AppFlagBadge';
import { AppSeverityChip } from '@/components/flags/AppSeverityChip';
import { AppSourceLink } from '@/components/flags/AppSourceLink';
import { AppTldr } from '@/components/flags/AppTldr';
import { useFlagPost } from '@/hooks/queries/useFlagPost';
import { formatDate } from '@/lib/date';
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

      <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
        <AppSeverityChip severity={flag.severity} />
        <AppFlagBadge type={flag.type} />
      </Stack>

      <Typography variant="h4" component="h1">
        {headline}
      </Typography>

      <AppTldr text={makeTldr(flag)} />

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('post:explanation')}
        </Typography>
        <Typography variant="body1">{flag.explanation_bg}</Typography>
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

      <Typography variant="caption" color="text.secondary">
        {t('flags:card.detectedAt')}: {formatDate(flag.detected_at)}
      </Typography>
    </Stack>
  );
}
