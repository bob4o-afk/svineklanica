import { describe, expect, it } from 'vitest';
import { approvedFlags, flagPosts } from '@/mocks/fixtures/data';

/** The fixtures are typed against the contract at compile time; these assertions lock the
 *  project's hard invariant at runtime: NO FLAG WITHOUT A SOURCE (unsourced = disinformation). */
describe('flag fixtures satisfy the contract', () => {
  it('every flag carries at least one source and a non-empty explanation', () => {
    expect(flagPosts.length).toBeGreaterThan(0);
    for (const flag of flagPosts) {
      expect(flag.public_id).toBeTruthy();
      expect(flag.sources.length).toBeGreaterThanOrEqual(1);
      expect(flag.explanation_bg.length).toBeGreaterThan(0);
      for (const ref of flag.sources) {
        expect(ref.url.length).toBeGreaterThan(0);
        expect(ref.fetched_at.length).toBeGreaterThan(0);
      }
    }
  });

  it('the approved set exposes only approved flags', () => {
    expect(approvedFlags.length).toBeGreaterThan(0);
    for (const flag of approvedFlags) {
      expect(flag.status).toBe('approved');
    }
  });
});
