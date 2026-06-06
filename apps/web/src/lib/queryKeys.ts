import type { FlagFeedQuery } from '@/types/api';

/** Central, typed react-query key factory. */
export const queryKeys = {
  flagFeed: (query: FlagFeedQuery) => ['flag-feed', query] as const,
  flagPost: (publicId: string) => ['flag-post', publicId] as const,
  authority: (publicId: string) => ['authority', publicId] as const,
  company: (eik: string) => ['company', eik] as const,
  priceSeries: (key: string) => ['price-series', key] as const,
  serialWinnerGraph: (publicId: string) => ['serial-winner-graph', publicId] as const,
  regionAggregate: (metric: string) => ['region-aggregate', metric] as const,
  search: (q: string) => ['search', q] as const,
  stats: () => ['stats'] as const,
  me: () => ['me'] as const,
  pendingFlags: () => ['admin', 'pending-flags'] as const,
  sources: () => ['admin', 'sources'] as const,
};
