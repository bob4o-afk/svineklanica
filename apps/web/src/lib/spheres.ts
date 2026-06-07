import {
  CoinsIcon,
  FileTextIcon,
  FirstAidKitIcon,
  GraduationCapIcon,
  type Icon,
  ScalesIcon,
  ShieldIcon,
} from '@phosphor-icons/react';
import type { CorruptionCategory, Sphere } from '@/types/api';

/** Icon + i18n label for a Sphere / Category badge. Colours/labels live in theme + i18n. */
interface BadgeMeta {
  icon: Icon;
  i18nKey: string;
}

/** Sphere (top level of Sphere → Category → severity, CLAUDE.md §1.0). */
export const sphereMeta: Record<Sphere, BadgeMeta> = {
  judiciary: { icon: ScalesIcon, i18nKey: 'flags:sphere.judiciary' },
  healthcare: { icon: FirstAidKitIcon, i18nKey: 'flags:sphere.healthcare' },
  police: { icon: ShieldIcon, i18nKey: 'flags:sphere.police' },
  education: { icon: GraduationCapIcon, i18nKey: 'flags:sphere.education' },
};

/** Corruption category — the abuse mechanism (middle level of the hierarchy). */
export const corruptionCategoryMeta: Record<CorruptionCategory, BadgeMeta> = {
  public_procurement: { icon: FileTextIcon, i18nKey: 'flags:category.public_procurement' },
  unregulated_payment: { icon: CoinsIcon, i18nKey: 'flags:category.unregulated_payment' },
};
