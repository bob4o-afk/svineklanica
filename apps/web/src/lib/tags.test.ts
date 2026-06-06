import { describe, expect, it } from 'vitest';
import { ALL_PUNK_TAGS, punkTagMeta } from './tags';

describe('punk tags', () => {
  it('every listed tag has meta pointing at a flags:tag.* i18n key', () => {
    expect(ALL_PUNK_TAGS.length).toBeGreaterThan(0);
    for (const tag of ALL_PUNK_TAGS) {
      expect(punkTagMeta[tag]).toBeDefined();
      expect(punkTagMeta[tag].i18nKey).toMatch(/^flags:tag\./);
    }
  });
});
