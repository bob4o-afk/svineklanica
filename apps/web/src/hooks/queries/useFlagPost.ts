import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { FlagPost } from '@/types/api';

export function useFlagPost(publicId: string) {
  return useQuery({
    queryKey: queryKeys.flagPost(publicId),
    queryFn: async () => {
      const response = await http.get<FlagPost>(`/flag-posts/${publicId}`);
      return response.data;
    },
    enabled: publicId.length > 0,
  });
}
