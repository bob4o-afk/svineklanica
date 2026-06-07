import { describe, expect, it } from 'vitest';
import type { MoneyAmount } from '@/types/api';
import {
  BGN_PER_EUR,
  bgnToEur,
  eurToBgn,
  formatMoney,
  formatMoneyAxis,
  formatMoneyShort,
  toEur,
} from './money';

function bgn(amount: number, vatIncluded = true): MoneyAmount {
  return { amount, currency: 'BGN', vat_included: vatIncluded };
}

function eur(amount: number, vatIncluded = true): MoneyAmount {
  return { amount, currency: 'EUR', vat_included: vatIncluded };
}

describe('peg conversion', () => {
  it('converts лв → € at the fixed peg', () => {
    expect(bgnToEur(BGN_PER_EUR)).toBeCloseTo(1);
  });

  it('converts € → лв at the fixed peg', () => {
    expect(eurToBgn(1)).toBeCloseTo(BGN_PER_EUR);
  });

  it('re-denominates a лв amount to EUR', () => {
    const result = toEur(bgn(BGN_PER_EUR));
    expect(result.currency).toBe('EUR');
    expect(result.amount).toBeCloseTo(1);
  });

  it('leaves an already-EUR amount untouched', () => {
    expect(toEur(eur(50))).toEqual(eur(50));
  });
});

describe('formatMoney', () => {
  it('renders лв amounts in euro (UI is euro-first)', () => {
    const formatted = formatMoney(bgn(1234));
    expect(formatted).toContain('€');
    expect(formatted).not.toContain('лв');
  });

  it('appends the no-VAT label when VAT is not included', () => {
    expect(formatMoney(bgn(1234, false))).toContain('(без ДДС)');
  });

  it('formats EUR with the euro symbol', () => {
    expect(formatMoney(eur(50))).toContain('€');
  });
});

describe('formatMoneyShort', () => {
  it('compacts millions', () => {
    expect(formatMoneyShort(eur(3_000_000))).toBe('3.0 млн €');
  });

  it('compacts thousands', () => {
    expect(formatMoneyShort(eur(2_000))).toBe('2 хил. €');
  });

  it('keeps small amounts as-is', () => {
    expect(formatMoneyShort(eur(500))).toBe('500 €');
  });

  it('converts a лв amount before compacting', () => {
    // 1.95583M лв ≈ 1.0M € → "1.0 млн €"
    expect(formatMoneyShort(bgn(1_000_000 * BGN_PER_EUR))).toBe('1.0 млн €');
  });
});

describe('formatMoneyAxis', () => {
  it('keeps a whole thousand without a decimal', () => {
    expect(formatMoneyAxis(eur(2_000))).toBe('2 хил. €');
  });

  it('shows one decimal for a half-thousand tick (so adjacent ticks stay distinct)', () => {
    expect(formatMoneyAxis(eur(1_500))).toBe('1,5 хил. €');
  });

  it('keeps sub-thousand amounts as-is', () => {
    expect(formatMoneyAxis(eur(500))).toBe('500 €');
  });
});
