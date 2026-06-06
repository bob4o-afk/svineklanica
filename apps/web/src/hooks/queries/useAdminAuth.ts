import { useMutation, useQueryClient } from '@tanstack/react-query';
import { env } from '@/config/env';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { AdminUser } from '@/types/api';

export interface LoginInput {
  email: string;
  password: string;
}

/** Sanctum's CSRF cookie lives at the API app root, NOT under `/api` — strip the suffix so the
 *  prime request hits `/sanctum/csrf-cookie` on the right origin (dev `/api` → same origin). */
function csrfBaseUrl(): string {
  return env.apiUrl.replace(/\/api\/?$/, '');
}

/** Sanctum SPA login: prime the CSRF cookie, POST credentials (the browser sends the session +
 *  XSRF header — no token in JS, see lib/http), then refresh the session query. */
export function useLogin() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ email, password }: LoginInput): Promise<AdminUser> => {
      await http.get('/sanctum/csrf-cookie', { baseURL: csrfBaseUrl() });
      const response = await http.post<AdminUser>('/admin/login', { email, password });
      return response.data;
    },
    onSuccess: (user) => {
      queryClient.setQueryData(queryKeys.me(), user);
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (): Promise<void> => {
      await http.post('/admin/logout');
    },
    onSuccess: () => {
      queryClient.setQueryData(queryKeys.me(), null);
      void queryClient.invalidateQueries();
    },
  });
}
