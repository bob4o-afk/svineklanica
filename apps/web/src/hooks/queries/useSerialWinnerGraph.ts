import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { SerialWinnerGraph } from '@/types/api';

/** Winner↔authority network for a company (serial-winner / shell-cluster story). */
export function useSerialWinnerGraph(publicId: string) {
  return useQuery({
    queryKey: queryKeys.serialWinnerGraph(publicId),
    queryFn: async () => {
      const response = await http.get<SerialWinnerGraph>(
        `/graphs/serial-winner/${encodeURIComponent(publicId)}`,
      );
      return response.data;
    },
    enabled: publicId.length > 0,
  });
}
