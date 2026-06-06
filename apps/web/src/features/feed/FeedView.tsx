import { Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';
import type { AppFilterValue } from '@/components/flags/AppFilterBar';
import { flagTypeMeta, severityMeta } from '@/lib/flags';
import type { FlagSeverity, FlagSort, FlagType } from '@/types/api';
import { FeedList } from './FeedList';
import { FeedToolbar } from './FeedToolbar';

/** Whitelists derived from the domain metadata — unknown URL values are dropped (security.md). */
const VALID_TYPES = new Set<string>(Object.keys(flagTypeMeta));
const VALID_SEVERITIES = new Set<string>(Object.keys(severityMeta));

function parseSort(value: string | null): FlagSort {
  return value === 'severity' ? 'severity' : 'newest';
}

function parseFilter(params: URLSearchParams): AppFilterValue {
  return {
    type: params.getAll('type').filter((x): x is FlagType => VALID_TYPES.has(x)),
    severity: params.getAll('severity').filter((x): x is FlagSeverity => VALID_SEVERITIES.has(x)),
  };
}

/** The feed page body: title, filters + sort toolbar, and the infinite list. Sort and filters live
 *  in the URL so a view is shareable. */
export function FeedView() {
  const { t } = useTranslation();
  const [params, setParams] = useSearchParams();
  const sort = parseSort(params.get('sort'));
  const filter = parseFilter(params);

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

  const setFilter = (next: AppFilterValue): void => {
    setParams(
      (prev) => {
        const copy = new URLSearchParams(prev);
        copy.delete('type');
        for (const type of next.type) copy.append('type', type);
        copy.delete('severity');
        for (const severity of next.severity) copy.append('severity', severity);
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
      <FeedToolbar sort={sort} onSortChange={setSort} filter={filter} onFilterChange={setFilter} />
      <FeedList query={{ sort, type: filter.type, severity: filter.severity }} />
    </Stack>
  );
}
