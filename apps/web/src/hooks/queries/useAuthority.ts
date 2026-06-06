import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { AuthorityDetail } from '@/types/api';

/** A contracting authority's profile: stats + its flag history. */
export function useAuthority(publicId: string) {
  return useQuery({
    queryKey: queryKeys.authority(publicId),
    queryFn: async () => {
      const response = await http.get<AuthorityDetail>(`/authorities/${publicId}`);
      return response.data;
    },
    enabled: publicId.length > 0,
  });
}
