import {
  ClockIcon,
  CoinsIcon,
  CopyIcon,
  type Icon,
  ProhibitIcon,
  RulerIcon,
  TrophyIcon,
  WarningIcon,
} from '@phosphor-icons/react';
import type { FlagSeverity, FlagType } from '@/types/api';

/** Single source for how each flag TYPE is presented (icon + i18n label). */
interface FlagTypeMeta {
  icon: Icon;
  i18nKey: string;
}

export const flagTypeMeta: Record<FlagType, FlagTypeMeta> = {
  price_discrepancy: { icon: CoinsIcon, i18nKey: 'flags:type.price_discrepancy' },
  tailored_spec: { icon: RulerIcon, i18nKey: 'flags:type.tailored_spec' },
  serial_winner: { icon: TrophyIcon, i18nKey: 'flags:type.serial_winner' },
  cancelled: { icon: ProhibitIcon, i18nKey: 'flags:type.cancelled' },
  implausible_scope: { icon: WarningIcon, i18nKey: 'flags:type.implausible_scope' },
  delayed_payment: { icon: ClockIcon, i18nKey: 'flags:type.delayed_payment' },
  doc_clone: { icon: CopyIcon, i18nKey: 'flags:type.doc_clone' },
};

/** Theme token used for a severity (mirrors tailwind/MUI palette). */
export type SeverityToken = 'alarm' | 'rust' | 'acid' | 'muted';

interface SeverityMeta {
  rank: number;
  token: SeverityToken;
  i18nKey: string;
}

export const severityMeta: Record<FlagSeverity, SeverityMeta> = {
  critical: { rank: 4, token: 'alarm', i18nKey: 'flags:severity.critical' },
  high: { rank: 3, token: 'alarm', i18nKey: 'flags:severity.high' },
  medium: { rank: 2, token: 'rust', i18nKey: 'flags:severity.medium' },
  low: { rank: 1, token: 'muted', i18nKey: 'flags:severity.low' },
};

export function severityRank(severity: FlagSeverity): number {
  return severityMeta[severity].rank;
}
