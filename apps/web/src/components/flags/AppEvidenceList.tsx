import { Stack, Typography } from '@mui/material';
import { EMPTY_CELL, emptyCell } from '@/lib/format';
import { formatMoney } from '@/lib/money';
import { fonts } from '@/theme/typography';
import type { EvidenceItem } from '@/types/api';

export interface AppEvidenceListProps {
  items: EvidenceItem[];
  /** Cap the number of rows (e.g. top 2 on a card). */
  max?: number;
}

function renderValue(item: EvidenceItem): string {
  if (item.money !== undefined) return formatMoney(item.money);
  return emptyCell(item.value);
}

/** The numbers behind a flag, as a definition list. Money is formatted; values never blank. */
export function AppEvidenceList({ items, max }: AppEvidenceListProps) {
  const shown = max !== undefined ? items.slice(0, max) : items;

  if (shown.length === 0) {
    return (
      <Typography variant="body2" color="text.secondary">
        {EMPTY_CELL}
      </Typography>
    );
  }

  return (
    <Stack component="dl" spacing={0.5} sx={{ m: 0 }}>
      {shown.map((item, index) => (
        <Stack key={`${item.label}-${index}`} direction="row" justifyContent="space-between" gap={2}>
          <Typography component="dt" variant="body2" color="text.secondary">
            {item.label}
          </Typography>
          <Typography
            component="dd"
            variant="body2"
            sx={{ m: 0, fontFamily: fonts.mono, fontWeight: 600, textAlign: 'right' }}
          >
            {renderValue(item)}
          </Typography>
        </Stack>
      ))}
    </Stack>
  );
}
