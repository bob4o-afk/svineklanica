import { describe, expect, it } from 'vitest';
import { findPriceOutlierIndex } from './outlier';

describe('findPriceOutlierIndex', () => {
  it('flags a single overpriced spike amid an otherwise reasonable series', () => {
    // index 2 (4200) is the rigged outlier — the demo "laptops" series.
    expect(findPriceOutlierIndex([1400, 1780, 4200, 2160, 2540, 2920])).toBe(2);
  });

  it('returns null for a steady price creep (no point truly stands out)', () => {
    expect(findPriceOutlierIndex([1400, 1780, 2160, 2540, 2920, 3300])).toBeNull();
  });

  it('returns null with too few captures to judge', () => {
    expect(findPriceOutlierIndex([100, 100, 9000])).toBeNull();
  });

  it('ignores cheap (below-median) points — overpricing only', () => {
    expect(findPriceOutlierIndex([5000, 5200, 5100, 100, 5300])).toBeNull();
  });

  it('picks the strongest spike when several sit above the median', () => {
    expect(findPriceOutlierIndex([1000, 1000, 1000, 1000, 4000, 9000])).toBe(5);
  });
});
