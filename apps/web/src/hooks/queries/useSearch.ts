import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { SearchResults } from '@/types/api';

/** Minimum query length before we hit the search endpoint (avoids 1-char noise/spam). */
export const SEARCH_MIN_LENGTH = 2;

/** Global search across authorities, companies, and tenders. Trims + length-gates the query;
 *  keeps previous results visible while the next query loads (smooth typing). */
export function useSearch(q: string) {
  const query = q.trim();
  return useQuery({
    queryKey: queryKeys.search(query),
    queryFn: async () => {
      const response = await http.get<SearchResults>(`/search?q=${encodeURIComponent(query)}`);
      return response.data;
    },
    enabled: query.length >= SEARCH_MIN_LENGTH,
    placeholderData: keepPreviousData,
  });
}
