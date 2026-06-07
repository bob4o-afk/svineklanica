import { useQuery } from '@tanstack/react-query';
import { isAppError } from '@/lib/errors';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { AdminUser } from '@/types/api';

/** The current session, from the backend `GET /api/admin/me` (auth.me, under the guarded admin
 *  namespace). A 401 is the normal "not logged in" answer — NOT an error — so it resolves to
 *  `null`; the public app never surfaces an error for an anonymous visitor. The real authority is
 *  always the server (security.md): this only drives UX (show admin nav / redirect). */
export function useMe() {
  return useQuery({
    queryKey: queryKeys.me(),
    queryFn: async (): Promise<AdminUser | null> => {
      try {
        const response = await http.get<AdminUser>('/admin/me');
        return response.data;
      } catch (error) {
        if (isAppError(error) && error.status === 401) return null;
        throw error;
      }
    },
    staleTime: 60_000,
    retry: false,
  });
}
