import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { FlagPost, Paginated } from '@/types/api';

/** The review queue — flags awaiting an editor's decision (status=pending). Admin-only; the
 *  endpoint enforces the session server-side. */
export function usePendingFlags() {
  return useQuery({
    queryKey: queryKeys.pendingFlags(),
    queryFn: async (): Promise<Paginated<FlagPost>> => {
      const response = await http.get<Paginated<FlagPost>>('/admin/flag-posts', {
        params: { status: 'pending', per_page: 50 },
      });
      return response.data;
    },
  });
}
