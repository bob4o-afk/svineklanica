import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { ProcurementSector, RegionAggregate } from '@/types/api';

/** Per-region flag aggregates that drive the corruption map. Pass a sector to show only that
 *  sector's flags per region (null = all sectors). */
export function useRegionAggregate(category?: ProcurementSector | null) {
  return useQuery({
    queryKey: queryKeys.regionAggregate(category ?? 'all'),
    queryFn: async () => {
      const query = category ? `?category=${encodeURIComponent(category)}` : '';
      const response = await http.get<RegionAggregate[]>(`/regions/aggregate${query}`);
      return response.data;
    },
  });
}
