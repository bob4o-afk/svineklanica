import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { PriceSeries } from '@/types/api';

/** Price-over-time series for one product/category key (the "watch it creep" graph). */
export function usePriceSeries(seriesKey: string) {
  return useQuery({
    queryKey: queryKeys.priceSeries(seriesKey),
    queryFn: async () => {
      const response = await http.get<PriceSeries>(`/price-series/${encodeURIComponent(seriesKey)}`);
      return response.data;
    },
    enabled: seriesKey.length > 0,
  });
}
