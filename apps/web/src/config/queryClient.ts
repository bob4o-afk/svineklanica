import { QueryCache, QueryClient } from '@tanstack/react-query';
import { toAppError } from '@/lib/errors';
import { logger } from '@/lib/logger';

export function makeQueryClient(): QueryClient {
  return new QueryClient({
    queryCache: new QueryCache({
      onError: (error) => {
        const appError = toAppError(error);
        logger.error('query_error', { message: appError.message, code: appError.code });
      },
    }),
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry: 1,
        refetchOnWindowFocus: false,
      },
    },
  });
}
