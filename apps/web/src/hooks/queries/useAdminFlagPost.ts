import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { FlagPost } from '@/types/api';

/** A single flag for review — unlike the public detail, this can return a pending/rejected flag. */
export function useAdminFlagPost(publicId: string) {
  return useQuery({
    queryKey: queryKeys.adminFlagPost(publicId),
    queryFn: async (): Promise<FlagPost> => {
      const response = await http.get<FlagPost>(`/admin/flag-posts/${encodeURIComponent(publicId)}`);
      return response.data;
    },
    enabled: publicId.length > 0,
  });
}
