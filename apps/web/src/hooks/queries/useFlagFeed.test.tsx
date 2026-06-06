import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it } from 'vitest';
import { useFlagFeed } from './useFlagFeed';

function createWrapper() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
  };
}

describe('useFlagFeed (MSW)', () => {
  it('loads the first page of approved flags and reports more pages', async () => {
    const { result } = renderHook(() => useFlagFeed({ sort: 'newest' }), {
      wrapper: createWrapper(),
    });

    expect(result.current.isPending).toBe(true);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    const firstPage = result.current.data?.pages[0];
    expect(firstPage?.data.length).toBeGreaterThan(0);
    // 24 approved fixtures at 6/page -> more pages exist.
    expect(result.current.hasNextPage).toBe(true);
  });
});
