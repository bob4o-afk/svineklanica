import { describe, expect, it } from 'vitest';
import { sectorFromCpv } from './sectors';

describe('sectorFromCpv', () => {
  it('maps known CPV prefixes to their sector (specific before generic)', () => {
    expect(sectorFromCpv('30213100')).toBe('it'); // laptops
    expect(sectorFromCpv('45233142')).toBe('roads'); // road repair (before 45 construction)
    expect(sectorFromCpv('33100000')).toBe('health'); // medical equipment
    expect(sectorFromCpv('80500000')).toBe('education'); // training services
    expect(sectorFromCpv('45212200')).toBe('construction'); // sports hall
    expect(sectorFromCpv('45231300')).toBe('utilities'); // water pipeline (before 45)
    expect(sectorFromCpv('15800000')).toBe('supplies'); // food
  });

  it('falls back to "other" for unknown, empty, or missing CPV', () => {
    expect(sectorFromCpv('37535200')).toBe('other');
    expect(sectorFromCpv('')).toBe('other');
    expect(sectorFromCpv(undefined)).toBe('other');
  });
});
