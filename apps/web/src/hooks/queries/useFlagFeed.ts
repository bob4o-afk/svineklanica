import { useInfiniteQuery } from '@tanstack/react-query';
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
  if (query.cpv) params.set('cpv', query.cpv);
  if (query.q) params.set('q', query.q);
  for (const type of query.type ?? []) params.append('type', type);
  for (const severity of query.severity ?? []) params.append('severity', severity);

  const response = await http.get<Paginated<FlagPost>>(`/flag-posts?${params.toString()}`);
  return response.data;
}

export function useFlagFeed(query: FlagFeedQuery = {}) {
  return useInfiniteQuery({
    queryKey: queryKeys.flagFeed(query),
    queryFn: ({ pageParam }) => fetchFeed(query, pageParam),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const loaded = lastPage.page * lastPage.per_page;
      return loaded < lastPage.total ? lastPage.page + 1 : undefined;
    },
  });
}
