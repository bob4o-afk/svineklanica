import { Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import type { FlagSort } from '@/types/api';
import { FeedList } from './FeedList';
import { FeedToolbar } from './FeedToolbar';

function parseSort(value: string | null): FlagSort {
  return value === 'severity' ? 'severity' : 'newest';
}

/** The feed page body: title, sort toolbar, and the infinite list. Sort lives in the URL so a
 *  view is shareable. */
export function FeedView() {
  const { t } = useTranslation();
  const [params, setParams] = useSearchParams();
  const sort = parseSort(params.get('sort'));

  const setSort = (next: FlagSort): void => {
    setParams(
      (prev) => {
        const copy = new URLSearchParams(prev);
        copy.set('sort', next);
        return copy;
      },
      { replace: true },
    );
  };

  return (
    <Stack spacing={2}>
      <Stack spacing={0.5}>
        <Typography variant="h4" component="h1">
          {t('feed:title')}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          {t('feed:subtitle')}
        </Typography>
      </Stack>
      <FeedToolbar sort={sort} onSortChange={setSort} />
      <FeedList query={{ sort }} />
    </Stack>
  );
}
