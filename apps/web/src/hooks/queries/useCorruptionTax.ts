import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { CorruptionTax } from '@/types/api';

/** Corruption-tax calculator: given the taxes the citizen paid, fetch the flagged
 *  share of public spend projected onto that amount (+ flag-post links). Only fires
 *  once the user has entered a positive amount and pressed calculate. */
export function useCorruptionTax(taxesPaid: number) {
  return useQuery({
    queryKey: queryKeys.corruptionTax(taxesPaid),
    queryFn: async () => {
      const response = await http.get<CorruptionTax>('/insights/corruption-tax', {
        params: { taxes_paid: taxesPaid },
      });
      return response.data;
    },
    enabled: taxesPaid > 0,
    // The corpus changes only when ingest/detect re-run — cache generously.
    staleTime: 5 * 60_000,
    gcTime: 30 * 60_000,
  });
}
