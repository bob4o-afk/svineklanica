import { infiniteQueryOptions, useInfiniteQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { FlagFeedQuery, FlagPost, Paginated } from '@/types/api';

const PER_PAGE = 6;

async function fetchFeed(query: FlagFeedQuery, page: number): Promise<Paginated<FlagPost>> {
  const params = new URLSearchParams();
  params.set('page', String(page));
  params.set('per_page', String(query.per_page ?? PER_PAGE));
  if (query.sort) params.set('sort', query.sort);
  if (query.region) params.set('region', query.region);
  if (query.q) params.set('q', query.q);
  for (const type of query.type ?? []) params.append('type', type);
  for (const category of query.category ?? []) params.append('category', category);
  for (const severity of query.severity ?? []) params.append('severity', severity);

  const response = await http.get<Paginated<FlagPost>>(`/flag-posts?${params.toString()}`);
  return response.data;
}

/** Shared query options so the feed can be both rendered (useFlagFeed) and prefetched
 *  (queryClient.prefetchInfiniteQuery) under the exact same cache key — lets the map warm a
 *  region's feed during the click animation so it lands instantly. */
export function feedQueryOptions(query: FlagFeedQuery = {}) {
  return infiniteQueryOptions({
    queryKey: queryKeys.flagFeed(query),
    queryFn: ({ pageParam }) => fetchFeed(query, pageParam),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const loaded = lastPage.page * lastPage.per_page;
      return loaded < lastPage.total ? lastPage.page + 1 : undefined;
    },
    // The feed is the home/feed hero content — cache it generously and serve stale-while-
    // revalidate so navigating away and back is instant instead of re-hitting the API every
    // time (frontend.md §9). Fresh for 5 min; kept in cache 30 min for instant remounts.
    staleTime: 5 * 60_000,
    gcTime: 30 * 60_000,
  });
}

export function useFlagFeed(query: FlagFeedQuery = {}) {
  return useInfiniteQuery(feedQueryOptions(query));
}
