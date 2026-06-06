import { Stack } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { AppButton } from '@/components/controls/AppButton';
import { AppEmptyState } from '@/components/feedback/AppEmptyState';
import { AppErrorState } from '@/components/feedback/AppErrorState';
import { AppFlagPostCard } from '@/components/flags/AppFlagPostCard';
import { AppFlagPostCardSkeleton } from '@/components/flags/AppFlagPostCardSkeleton';
import { AppReveal } from '@/components/motion/AppReveal';
import { useFlagFeed } from '@/hooks/queries/useFlagFeed';
import type { FlagFeedQuery } from '@/types/api';

export interface FeedListProps {
  query?: FlagFeedQuery;
  /** When set, render only the first N flags and hide the "load more" control (teaser mode). */
  limit?: number;
}

/** The infinite flag feed. Shows skeleton → cards → "load more", with explicit error/empty
 *  states (frontend.md §7). In teaser mode it renders a capped, non-paginating slice. */
export function FeedList({ query = {}, limit }: FeedListProps) {
  const { t } = useTranslation();
  const feed = useFlagFeed(query);

  if (feed.isPending) {
    // Card-shaped skeletons so the feed reserves the real сигнал-card size (no layout jump).
    return (
      <Stack spacing={2} aria-busy="true" aria-live="polite">
        {Array.from({ length: limit ?? 4 }, (_, i) => (
          <AppFlagPostCardSkeleton key={i} />
        ))}
      </Stack>
    );
  }
  // Only take over the whole view when there's nothing to show. A failed background refetch /
  // fetchNextPage keeps the already-loaded cards on screen (the "load more" button retries).
  if (feed.isError && feed.data === undefined) {
    return (
      <AppErrorState
        title={t('feed:error.title')}
        message={t('feed:error.body')}
        onRetry={() => void feed.refetch()}
      />
    );
  }

  const flags = (feed.data?.pages ?? []).flatMap((page) => page.data);
  const shown = limit !== undefined ? flags.slice(0, limit) : flags;

  if (shown.length === 0) {
    return <AppEmptyState title={t('feed:empty.title')} description={t('feed:empty.body')} />;
  }

  return (
    <Stack spacing={2}>
      {shown.map((flag, index) => (
        // Cards fade/slide in as they scroll into view; the stagger caps so a long page never
        // waits seconds for the last card (the modulo keeps each newly-loaded batch lively).
        <AppReveal key={flag.public_id} delay={(index % 5) * 70}>
          <AppFlagPostCard flag={flag} />
        </AppReveal>
      ))}
      {limit === undefined && feed.hasNextPage ? (
        <AppButton
          variant="outlined"
          onClick={() => void feed.fetchNextPage()}
          disabled={feed.isFetchingNextPage}
          sx={{ alignSelf: 'center' }}
        >
          {t('common:actions.loadMore')}
        </AppButton>
      ) : null}
    </Stack>
  );
}
