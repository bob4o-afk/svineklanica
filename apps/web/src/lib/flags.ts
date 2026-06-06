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
import i18n from '@/i18n';
import { formatMoney } from '@/lib/money';
import type { FlagPost, FlagSeverity, FlagType } from '@/types/api';

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

/** A one-line, number-forward „Накратко" gist for a post — so a reader gets the point before
 *  deciding to read the full explanation. Derived from the served flag (subject + evidence), so it
 *  is guaranteed on every post without a new backend field; the per-type wording lives in i18n
 *  (`post:tldrByType.*`). Money is run through the single `formatMoney` formatter (frontend.md §6). */
export function makeTldr(flag: FlagPost): string {
  const moneyItem = flag.evidence.find((e) => e.money !== undefined);
  const value = moneyItem?.money !== undefined ? formatMoney(moneyItem.money) : '';
  const statItem = flag.evidence.find((e) => e.money === undefined);
  const stat = statItem !== undefined ? String(statItem.value) : '';
  const company = flag.subject.company?.name ?? '';
  const authority = flag.subject.authority?.name ?? '';
  return i18n.t(`post:tldrByType.${flag.type}`, { stat, company, authority, value });
}
