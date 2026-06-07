import i18n from '@/i18n';
import type { MoneyAmount } from '@/types/api';

/** BGN is pegged to the euro at the official fixed rate: 1 EUR = 1.95583 BGN. The backend
 *  stores/serves nominal лв (data-sources.md), but the UI is euro-first — every amount is
 *  re-denominated through {@link toEur} before it's shown. */
export const BGN_PER_EUR = 1.95583;

/** Convert a лв amount to euro at the fixed peg. */
export function bgnToEur(amount: number): number {
  return amount / BGN_PER_EUR;
}

/** Convert a euro amount to лв at the fixed peg (e.g. a euro tax input → the лв the API expects). */
export function eurToBgn(amount: number): number {
  return amount * BGN_PER_EUR;
}

/** Re-denominate a money value into EUR (no-op if it's already EUR). The single boundary where
 *  лв becomes € — every formatter below runs amounts through it, so the whole UI shows euro. */
export function toEur(money: MoneyAmount): MoneyAmount {
  if (money.currency === 'EUR') return money;
  return { amount: bgnToEur(money.amount), currency: 'EUR', vat_included: money.vat_included };
}

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
  const eur = toEur(money);
  const base = getFormatter(eur.currency).format(eur.amount);
  return eur.vat_included ? base : `${base} (${i18n.t('common:units.vatExcluded')})`;
}

/** Compact form for cards/badges: 1 200 000 лв -> "613.6 хил €". */
export function formatMoneyShort(money: MoneyAmount): string {
  const eur = toEur(money);
  const suffix =
    eur.currency === 'BGN' ? i18n.t('common:units.bgnShort') : i18n.t('common:units.eurShort');
  const million = i18n.t('common:units.million');
  const thousand = i18n.t('common:units.thousand');
  const n = eur.amount;
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)} ${million} ${suffix}`;
  if (n >= 1_000) return `${Math.round(n / 1_000)} ${thousand} ${suffix}`;
  return `${Math.round(n)} ${suffix}`;
}

/** Compact money for chart **axes**: like `formatMoneyShort` but with adaptive precision so
 *  500-step ticks stay distinct (1500 -> "1,5 хил €", 2000 -> "2 хил €"). `formatMoneyShort`
 *  rounds to the nearest thousand, which collides adjacent axis ticks — keep that one for
 *  cards/badges and use this only on the price chart's y-axis. */
export function formatMoneyAxis(money: MoneyAmount): string {
  const eur = toEur(money);
  const suffix =
    eur.currency === 'BGN' ? i18n.t('common:units.bgnShort') : i18n.t('common:units.eurShort');
  const million = i18n.t('common:units.million');
  const thousand = i18n.t('common:units.thousand');
  const n = eur.amount;
  const compact = (value: number): string =>
    new Intl.NumberFormat('bg-BG', { maximumFractionDigits: 1 }).format(value);
  if (Math.abs(n) >= 1_000_000) return `${compact(n / 1_000_000)} ${million} ${suffix}`;
  if (Math.abs(n) >= 1_000) return `${compact(n / 1_000)} ${thousand} ${suffix}`;
  return `${compact(n)} ${suffix}`;
}
