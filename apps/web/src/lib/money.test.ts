import { describe, expect, it } from 'vitest';
import type { MoneyAmount } from '@/types/api';
import { formatMoney, formatMoneyAxis, formatMoneyShort } from './money';

function bgn(amount: number, vatIncluded = true): MoneyAmount {
  return { amount, currency: 'BGN', vat_included: vatIncluded };
}

describe('formatMoney', () => {
  it('formats BGN with the lev symbol', () => {
    expect(formatMoney(bgn(1234))).toContain('лв');
  });

  it('appends the no-VAT label when VAT is not included', () => {
    expect(formatMoney(bgn(1234, false))).toContain('(без ДДС)');
  });

  it('formats EUR with the euro symbol', () => {
    expect(formatMoney({ amount: 50, currency: 'EUR', vat_included: true })).toContain('€');
  });
});

describe('formatMoneyShort', () => {
  it('compacts millions', () => {
    expect(formatMoneyShort(bgn(1_200_000))).toBe('1.2 млн лв');
  });

  it('compacts thousands', () => {
    expect(formatMoneyShort(bgn(2_000))).toBe('2 хил. лв');
  });

  it('keeps small amounts as-is', () => {
    expect(formatMoneyShort(bgn(500))).toBe('500 лв');
  });

  it('uses the euro suffix for EUR', () => {
    expect(formatMoneyShort({ amount: 3_000_000, currency: 'EUR', vat_included: true })).toBe('3.0 млн €');
  });
});

describe('formatMoneyAxis', () => {
  it('keeps a whole thousand without a decimal', () => {
    expect(formatMoneyAxis(bgn(2_000))).toBe('2 хил. лв');
  });

  it('shows one decimal for a half-thousand tick (so adjacent ticks stay distinct)', () => {
    expect(formatMoneyAxis(bgn(1_500))).toBe('1,5 хил. лв');
  });

  it('keeps sub-thousand amounts as-is', () => {
    expect(formatMoneyAxis(bgn(500))).toBe('500 лв');
  });
});
