import { useQuery } from '@tanstack/react-query';
import type { FlagMapPoint } from '@/lib/mapPoints';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';

/** Every flag that carries a location, as lightweight points to pin on the map (CLAUDE.md §1.2).
 *  Served by `GET /api/map/flag-points` from the denormalised `flags.region_code` column, so it's
 *  a single small request — no over-fetching the full feed. */
export function useFlagMapPoints() {
  return useQuery({
    queryKey: queryKeys.flagMapPoints(),
    queryFn: async (): Promise<FlagMapPoint[]> => {
      const response = await http.get<FlagMapPoint[]>('/map/flag-points');
      return response.data;
    },
    staleTime: 5 * 60_000,
  });
}
