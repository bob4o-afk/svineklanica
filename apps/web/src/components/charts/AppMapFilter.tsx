import { Chip, Stack, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { ALL_SECTORS, sectorMeta } from '@/lib/sectors';
import type { ProcurementSector } from '@/types/api';

export interface AppMapFilterProps {
  value: ProcurementSector | null;
  onChange: (next: ProcurementSector | null) => void;
}

/** Single-select sector filter above the map: „Всички" + one chip per sector. Selecting one
 *  re-shades the map to that sector's flags only. */
export function AppMapFilter({ value, onChange }: AppMapFilterProps) {
  const { t } = useTranslation();

  return (
    <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap" useFlexGap>
      <Typography variant="overline" color="text.secondary">
        {t('sectors:filter')}
      </Typography>
      <Chip
        label={t('viz:map.allSectors')}
        size="small"
        color={value === null ? 'primary' : 'default'}
        variant={value === null ? 'filled' : 'outlined'}
        onClick={() => onChange(null)}
      />
      {ALL_SECTORS.map((sector) => {
        const active = value === sector;
        const SectorIcon = sectorMeta[sector].icon;
        return (
          <Chip
            key={sector}
            icon={<SectorIcon size={14} weight="bold" />}
            label={t(sectorMeta[sector].i18nKey)}
            size="small"
            color={active ? 'primary' : 'default'}
            variant={active ? 'filled' : 'outlined'}
            onClick={() => onChange(active ? null : sector)}
          />
        );
      })}
    </Stack>
  );
}
