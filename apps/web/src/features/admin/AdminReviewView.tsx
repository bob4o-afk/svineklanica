import { Alert, Chip, Divider, Stack, Typography } from '@mui/material';
import { ArrowLeftIcon, CheckCircleIcon, XCircleIcon } from '@phosphor-icons/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { AppButton } from '@/components/controls/AppButton';
import { AppTextField } from '@/components/controls/AppTextField';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppSkeleton } from '@/components/feedback/AppSkeleton';
import { AppSeo } from '@/components/layout/AppSeo';
import { AppEvidenceList } from '@/components/flags/AppEvidenceList';
import { AppFlagBadge } from '@/components/flags/AppFlagBadge';
import { AppSectorBadge } from '@/components/flags/AppSectorBadge';
import { AppSeverityChip } from '@/components/flags/AppSeverityChip';
import { AppSourceLink } from '@/components/flags/AppSourceLink';
import { useAdminFlagPost } from '@/hooks/queries/useAdminFlagPost';
import { useApproveFlag, useRejectFlag } from '@/hooks/queries/useReviewFlag';
import { useToast } from '@/hooks/useToast';
import { flagTypeMeta } from '@/lib/flags';
import { ALL_PUNK_TAGS, punkTagMeta } from '@/lib/tags';
import { paths } from '@/routes/paths';
import type { FlagPost, PunkTag } from '@/types/api';

/** The editable review form for one loaded flag. Initialised from the flag (no effect-sync) so the
 *  editor edits the detector's draft, then publishes with punk tags or rejects. */
function ReviewForm({ flag }: { flag: FlagPost }) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const { showToast } = useToast();
  const approve = useApproveFlag();
  const reject = useRejectFlag();

  const [title, setTitle] = useState(flag.title ?? '');
  const [explanation, setExplanation] = useState(flag.explanation_bg);
  const [note, setNote] = useState('');
  const [tags, setTags] = useState<PunkTag[]>(flag.tags ?? []);

  const busy = approve.isPending || reject.isPending;
  const headline = flag.title ?? t(flagTypeMeta[flag.type].i18nKey);

  function toggleTag(tag: PunkTag) {
    setTags((current) => (current.includes(tag) ? current.filter((x) => x !== tag) : [...current, tag]));
  }

  function onApprove() {
    approve.mutate(
      {
        publicId: flag.public_id,
        decision: {
          ...(title !== '' ? { title } : {}),
          ...(explanation !== '' ? { explanation_bg: explanation } : {}),
          ...(note !== '' ? { note } : {}),
          tags,
        },
      },
      {
        onSuccess: () => {
          showToast(t('admin:review.approved'), 'success');
          navigate(paths.adminPending);
        },
        onError: () => showToast(t('admin:review.error'), 'error'),
      },
    );
  }

  function onReject() {
    reject.mutate(flag.public_id, {
      onSuccess: () => {
        showToast(t('admin:review.rejected'), 'info');
        navigate(paths.adminPending);
      },
      onError: () => showToast(t('admin:review.error'), 'error'),
    });
  }

  return (
    <Stack spacing={3}>
      <AppButton
        variant="text"
        to={paths.adminPending}
        startIcon={<ArrowLeftIcon />}
        sx={{ alignSelf: 'flex-start' }}
      >
        {t('common:actions.back')}
      </AppButton>

      <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
        <AppSeverityChip severity={flag.severity} />
        <AppFlagBadge type={flag.type} />
        {flag.category !== undefined ? <AppSectorBadge sector={flag.category} /> : null}
      </Stack>

      <Typography variant="h4" component="h1">
        {headline}
      </Typography>

      {/* Verify sources — the non-negotiable step: no reachable source, no publish. */}
      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('admin:review.verifySources')}
        </Typography>
        <Alert severity="warning" sx={{ mb: 1.5 }}>
          {t('admin:review.verifyHint')}
        </Alert>
        <Stack spacing={1}>
          {flag.sources.map((source, index) => (
            <AppSourceLink key={`${source.url}-${index}`} source={source} />
          ))}
        </Stack>
      </section>

      <section>
        <Typography variant="h6" component="h2" gutterBottom>
          {t('post:evidence')}
        </Typography>
        <AppEvidenceList items={flag.evidence} />
      </section>

      <Divider />

      {/* Editorial edits */}
      <AppTextField
        label={t('admin:review.editTitle')}
        value={title}
        onChange={(event) => setTitle(event.target.value)}
      />
      <AppTextField
        label={t('admin:review.editExplanation')}
        value={explanation}
        onChange={(event) => setExplanation(event.target.value)}
        multiline
        minRows={3}
      />

      <section>
        <Typography variant="subtitle2" gutterBottom>
          {t('admin:review.tags')}
        </Typography>
        <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
          {ALL_PUNK_TAGS.map((tag) => {
            const selected = tags.includes(tag);
            const meta = punkTagMeta[tag];
            const TagIcon = meta.icon;
            return (
              <Chip
                key={tag}
                clickable
                onClick={() => toggleTag(tag)}
                color={selected ? 'secondary' : 'default'}
                variant={selected ? 'filled' : 'outlined'}
                icon={<TagIcon size={14} weight={selected ? 'fill' : 'regular'} />}
                label={t(meta.i18nKey)}
              />
            );
          })}
        </Stack>
      </section>

      <AppTextField
        label={t('admin:review.note')}
        value={note}
        onChange={(event) => setNote(event.target.value)}
        multiline
        minRows={2}
      />

      <Stack direction="row" spacing={1.5} flexWrap="wrap" useFlexGap>
        <AppButton color="success" startIcon={<CheckCircleIcon />} onClick={onApprove} disabled={busy}>
          {t('admin:review.approve')}
        </AppButton>
        <AppButton
          variant="outlined"
          color="error"
          startIcon={<XCircleIcon />}
          onClick={onReject}
          disabled={busy}
        >
          {t('admin:review.reject')}
        </AppButton>
      </Stack>
    </Stack>
  );
}

/** Loads a single flag for review; the editable form mounts only once the flag is in hand. */
export function AdminReviewView({ publicId }: { publicId: string }) {
  const { t } = useTranslation();
  const post = useAdminFlagPost(publicId);

  if (post.isPending) return <AppSkeleton count={3} />;
  if (post.isError) {
    return (
      <AppErrorState
        title={t('admin:review.notFound.title')}
        message={t('admin:review.notFound.body')}
        error={post.error}
      />
    );
  }

  return (
    <>
      <AppSeo title={t('admin:review.title')} noindex />
      <ReviewForm flag={post.data} />
    </>
  );
}
