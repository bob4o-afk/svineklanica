import {
  BuildingsIcon,
  DesktopIcon,
  DropIcon,
  FirstAidKitIcon,
  GraduationCapIcon,
  type Icon,
  PackageIcon,
  RoadHorizonIcon,
  TagIcon,
} from '@phosphor-icons/react';
import type { ProcurementSector } from '@/types/api';

/** Single source for how each procurement SECTOR is presented (icon + i18n label). */
interface SectorMeta {
  icon: Icon;
  i18nKey: string;
}

export const sectorMeta: Record<ProcurementSector, SectorMeta> = {
  health: { icon: FirstAidKitIcon, i18nKey: 'sectors:name.health' },
  education: { icon: GraduationCapIcon, i18nKey: 'sectors:name.education' },
  roads: { icon: RoadHorizonIcon, i18nKey: 'sectors:name.roads' },
  construction: { icon: BuildingsIcon, i18nKey: 'sectors:name.construction' },
  it: { icon: DesktopIcon, i18nKey: 'sectors:name.it' },
  utilities: { icon: DropIcon, i18nKey: 'sectors:name.utilities' },
  supplies: { icon: PackageIcon, i18nKey: 'sectors:name.supplies' },
  other: { icon: TagIcon, i18nKey: 'sectors:name.other' },
};

/** Display/filter order. */
export const ALL_SECTORS: readonly ProcurementSector[] = [
  'health',
  'education',
  'roads',
  'construction',
  'it',
  'utilities',
  'supplies',
  'other',
];

/** Map a CPV code to a sector. CPV is the reliable category key (data-sources.md §3); the
 *  backend should derive this the same way at ingest. Order matters: the more specific road /
 *  water prefixes are checked before the generic construction `45`. */
export function sectorFromCpv(cpv?: string): ProcurementSector {
  const code = (cpv ?? '').replace(/\D/g, '');
  if (code === '') return 'other';
  if (code.startsWith('45233') || code.startsWith('342') || code.startsWith('60')) return 'roads';
  if (code.startsWith('33') || code.startsWith('85')) return 'health';
  if (code.startsWith('80')) return 'education';
  if (
    code.startsWith('30') ||
    code.startsWith('48') ||
    code.startsWith('72') ||
    code.startsWith('32')
  ) {
    return 'it';
  }
  if (
    code.startsWith('45231') ||
    code.startsWith('90') ||
    code.startsWith('65') ||
    code.startsWith('09')
  ) {
    return 'utilities';
  }
  if (code.startsWith('45')) return 'construction';
  if (code.startsWith('15') || code.startsWith('03')) return 'supplies';
  return 'other';
}
