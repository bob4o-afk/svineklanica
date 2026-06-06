import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it } from 'vitest';
import { useAuthority } from './useAuthority';
import { useCompany } from './useCompany';
import { useSearch } from './useSearch';

function createWrapper() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: 0 } } });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
  };
}

describe('useAuthority (MSW)', () => {
  it('loads an authority profile with its stats', async () => {
    const { result } = renderHook(() => useAuthority('auth-1'), { wrapper: createWrapper() });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.authority.public_id).toBe('auth-1');
    expect(result.current.data?.stats.flag_count).toBeGreaterThanOrEqual(0);
  });
});

describe('useCompany (MSW)', () => {
  it('loads a company by EIK with a related-companies list', async () => {
    const { result } = renderHook(() => useCompany('200111222'), { wrapper: createWrapper() });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.company.eik).toBe('200111222');
    expect(Array.isArray(result.current.data?.related)).toBe(true);
  });
});

describe('useSearch (MSW)', () => {
  it('stays idle below the min length, then finds matches', async () => {
    const wrapper = createWrapper();
    const short = renderHook(() => useSearch('a'), { wrapper });
    expect(short.result.current.fetchStatus).toBe('idle');

    const { result } = renderHook(() => useSearch('Долна'), { wrapper });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.authorities.length).toBeGreaterThan(0);
  });
});
