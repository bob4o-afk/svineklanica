import { Stack } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { AppSelect } from '@/components/controls/AppSelect';
import type { FlagSort } from '@/types/api';

export interface FeedToolbarProps {
  sort: FlagSort;
  onSortChange: (sort: FlagSort) => void;
}

/** Feed controls. Phase 1 = sort only (newest / by severity); filters land in Phase 2. */
export function FeedToolbar({ sort, onSortChange }: FeedToolbarProps) {
  const { t } = useTranslation();

  return (
    <Stack direction="row" justifyContent="flex-end" sx={{ mb: 1 }}>
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
  );
}
