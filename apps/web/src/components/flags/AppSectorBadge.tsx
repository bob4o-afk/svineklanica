import { Chip } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { sectorMeta } from '@/lib/sectors';
import type { ProcurementSector } from '@/types/api';

export interface AppSectorBadgeProps {
  sector: ProcurementSector;
  size?: 'small' | 'medium';
}

/** Sector tag (училище / болница / път …) — icon + label, neutral outline so it reads as a
 *  category, not a severity. Derived from CPV upstream (lib/sectors). */
export function AppSectorBadge({ sector, size = 'small' }: AppSectorBadgeProps) {
  const { t } = useTranslation();
  const meta = sectorMeta[sector];
  const SectorIcon = meta.icon;

  return (
    <Chip
      size={size}
      variant="outlined"
      icon={<SectorIcon size={14} weight="bold" />}
      label={t(meta.i18nKey)}
    />
  );
}
