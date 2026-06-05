import { describe, expect, it } from 'vitest';
import { emptyCell, EMPTY_CELL, formatEik, formatNumber, formatPercent } from './format';

describe('emptyCell', () => {
  it('returns the em-dash for null / undefined / empty string', () => {
    expect(emptyCell(null)).toBe(EMPTY_CELL);
    expect(emptyCell(undefined)).toBe(EMPTY_CELL);
    expect(emptyCell('')).toBe(EMPTY_CELL);
  });

  it('keeps zero (a real value, not "empty")', () => {
    expect(emptyCell(0)).toBe('0');
  });

  it('stringifies real values', () => {
    expect(emptyCell('Община')).toBe('Община');
    expect(emptyCell(42)).toBe('42');
  });
});

describe('formatEik', () => {
  it('trims surrounding whitespace', () => {
    expect(formatEik('  200111222 ')).toBe('200111222');
  });
});

describe('formatNumber / formatPercent', () => {
  it('groups large numbers', () => {
    // bg-BG groups thousands (with a separator) — assert all digits survive.
    expect(formatNumber(1234567).replace(/\D/g, '')).toBe('1234567');
  });

  it('formats a ratio as a percent', () => {
    expect(formatPercent(0.5)).toContain('50');
  });
});
