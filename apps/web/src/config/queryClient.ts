import { QueryCache, QueryClient } from '@tanstack/react-query';
import { createSyncStoragePersister } from '@tanstack/query-sync-storage-persister';
import type { PersistQueryClientOptions } from '@tanstack/react-query-persist-client';
import { toAppError } from '@/lib/errors';
import { logger } from '@/lib/logger';

/** localStorage key for the persisted cache, and a buster — bump it after a breaking change to
 *  any cached API shape so old clients drop their stored cache instead of rendering stale data. */
const PERSIST_KEY = 'svk-query-cache';
const PERSIST_BUSTER = '1';
/** How long a persisted entry may be reused on boot — matches the feed's gcTime (useFlagFeed). */
const PERSIST_MAX_AGE = 30 * 60_000;
/** Never persist auth/admin queries to disk (stale/sensitive) — only public read content. */
const NON_PERSISTED_ROOTS = new Set(['me', 'admin']);

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
        // Persisted queries must survive in cache long enough to be restored on the next visit.
        gcTime: PERSIST_MAX_AGE,
        retry: 1,
        refetchOnWindowFocus: false,
      },
    },
  });
}

/** Persist the query cache to localStorage so a full page reload rehydrates instantly
 *  (stale-while-revalidate) instead of refetching from scratch — frontend.md §9. Returns the
 *  options for PersistQueryClientProvider, or null when storage is unavailable. */
export function makePersistOptions(): Omit<PersistQueryClientOptions, 'queryClient'> | null {
  if (typeof window === 'undefined') return null;
  return {
    persister: createSyncStoragePersister({ storage: window.localStorage, key: PERSIST_KEY }),
    maxAge: PERSIST_MAX_AGE,
    buster: PERSIST_BUSTER,
    dehydrateOptions: {
      shouldDehydrateQuery: (query) =>
        query.state.status === 'success' && !NON_PERSISTED_ROOTS.has(String(query.queryKey[0])),
    },
  };
}
