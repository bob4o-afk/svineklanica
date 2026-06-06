import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { RegionAggregate } from '@/types/api';

/** Per-region flag aggregates that drive the corruption map. */
export function useRegionAggregate(metric = 'flag_count') {
  return useQuery({
    queryKey: queryKeys.regionAggregate(metric),
    queryFn: async () => {
      const response = await http.get<RegionAggregate[]>('/regions/aggregate');
      return response.data;
    },
  });
}
