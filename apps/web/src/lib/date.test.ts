import { describe, expect, it } from 'vitest';
import { formatDate, formatDateTime, formatRelative } from './date';

// Midday UTC so the calendar day can't drift across CI time zones.
const ISO = '2026-01-15T12:00:00Z';

describe('date helpers (bg locale)', () => {
  it('formatDate keeps the day and year', () => {
    const out = formatDate(ISO);
    expect(out).toContain('15');
    expect(out).toContain('2026');
  });

  it('formatDateTime includes a time component', () => {
    expect(formatDateTime(ISO)).toMatch(/\d{1,2}:\d{2}/);
  });

  it('formatRelative returns a non-empty Bulgarian phrase', () => {
    expect(formatRelative(ISO).length).toBeGreaterThan(0);
  });
});
