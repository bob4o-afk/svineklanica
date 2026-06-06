import { describe, expect, it } from 'vitest';
import '@/i18n';
import { makeTldr } from '@/lib/flags';
import { formatMoney } from '@/lib/money';
import { flagPosts } from '@/mocks/fixtures/data';

describe('makeTldr', () => {
  it('produces a non-empty one-liner for every fixture and includes the contracted value', () => {
    for (const flag of flagPosts) {
      const tldr = makeTldr(flag);
      expect(tldr.length).toBeGreaterThan(0);
      // the resolved i18n template must not leak its raw key/placeholders
      expect(tldr).not.toContain('tldrByType');
      expect(tldr).not.toContain('{{');

      const moneyItem = flag.evidence.find((e) => e.money !== undefined);
      if (moneyItem?.money !== undefined) {
        expect(tldr).toContain(formatMoney(moneyItem.money));
      }
    }
  });
});
