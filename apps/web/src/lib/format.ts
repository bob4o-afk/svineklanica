/** Shared display helpers (write once, reuse). Empty values never render as nothing. */

export const EMPTY_CELL = '—';

export function emptyCell(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') return EMPTY_CELL;
  return String(value);
}

const numberFormatter = new Intl.NumberFormat('bg-BG');

export function formatNumber(value: number): string {
  return numberFormatter.format(value);
}

export function formatPercent(value: number, fractionDigits = 0): string {
  return new Intl.NumberFormat('bg-BG', {
    style: 'percent',
    maximumFractionDigits: fractionDigits,
  }).format(value);
}

/** Bulgarian company id (ЕИК/БУЛСТАТ) — normalize whitespace only. */
export function formatEik(eik: string): string {
  return eik.trim();
}
