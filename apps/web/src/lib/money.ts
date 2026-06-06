import i18n from '@/i18n';
import type { MoneyAmount } from '@/types/api';

const formatters: Record<string, Intl.NumberFormat> = {};

function getFormatter(currency: string): Intl.NumberFormat {
  const existing = formatters[currency];
  if (existing !== undefined) return existing;
  const created = new Intl.NumberFormat('bg-BG', {
    style: 'currency',
    currency,
    maximumFractionDigits: 0,
  });
  formatters[currency] = created;
  return created;
}

/** Full currency formatting, VAT-aware. Procurement values are huge — keep them readable. */
export function formatMoney(money: MoneyAmount): string {
  const base = getFormatter(money.currency).format(money.amount);
  return money.vat_included ? base : `${base} (${i18n.t('common:units.vatExcluded')})`;
}

/** Compact form for cards/badges: 1 200 000 -> "1.2 млн лв". */
export function formatMoneyShort(money: MoneyAmount): string {
  const suffix =
    money.currency === 'BGN' ? i18n.t('common:units.bgnShort') : i18n.t('common:units.eurShort');
  const million = i18n.t('common:units.million');
  const thousand = i18n.t('common:units.thousand');
  const n = money.amount;
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)} ${million} ${suffix}`;
  if (n >= 1_000) return `${Math.round(n / 1_000)} ${thousand} ${suffix}`;
  return `${n} ${suffix}`;
}

/** Compact money for chart **axes**: like `formatMoneyShort` but with adaptive precision so
 *  500-step ticks stay distinct (1500 -> "1,5 хил. лв", 2000 -> "2 хил. лв"). `formatMoneyShort`
 *  rounds to the nearest thousand, which collides adjacent axis ticks — keep that one for
 *  cards/badges and use this only on the price chart's y-axis. */
export function formatMoneyAxis(money: MoneyAmount): string {
  const suffix =
    money.currency === 'BGN' ? i18n.t('common:units.bgnShort') : i18n.t('common:units.eurShort');
  const million = i18n.t('common:units.million');
  const thousand = i18n.t('common:units.thousand');
  const n = money.amount;
  const compact = (value: number): string =>
    new Intl.NumberFormat('bg-BG', { maximumFractionDigits: 1 }).format(value);
  if (Math.abs(n) >= 1_000_000) return `${compact(n / 1_000_000)} ${million} ${suffix}`;
  if (Math.abs(n) >= 1_000) return `${compact(n / 1_000)} ${thousand} ${suffix}`;
  return `${compact(n)} ${suffix}`;
}
