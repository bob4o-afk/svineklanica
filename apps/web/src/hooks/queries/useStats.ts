import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { PlatformStats } from '@/types/api';

/** Home-hero headline counters (tenders / flags / detectors) — real totals from the API. */
export function useStats() {
  return useQuery({
    queryKey: queryKeys.stats(),
    queryFn: async () => {
      const response = await http.get<PlatformStats>('/stats');
      return response.data;
    },
    staleTime: 5 * 60_000,
  });
}
