import { Stack } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { AppSelect } from '@/components/controls/AppSelect';
import { AppFilterBar, type AppFilterValue } from '@/components/flags/AppFilterBar';
import type { FlagSort } from '@/types/api';

export interface FeedToolbarProps {
  sort: FlagSort;
  onSortChange: (sort: FlagSort) => void;
  filter: AppFilterValue;
  onFilterChange: (next: AppFilterValue) => void;
}

/** Feed controls: faceted filters (severity + type) and the sort select. */
export function FeedToolbar({ sort, onSortChange, filter, onFilterChange }: FeedToolbarProps) {
  const { t } = useTranslation();

  return (
    <Stack spacing={2} sx={{ mb: 1 }}>
      <AppFilterBar value={filter} onChange={onFilterChange} />
      <Stack direction="row" justifyContent="flex-end">
        <AppSelect
          id="feed-sort"
          label={t('feed:sort.label')}
          value={sort}
          options={[
            { value: 'newest', label: t('feed:sort.newest') },
            { value: 'severity', label: t('feed:sort.severity') },
          ]}
          onChange={(value) => onSortChange(value === 'severity' ? 'severity' : 'newest')}
        />
      </Stack>
    </Stack>
  );
}
