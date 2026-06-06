import { useQuery } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { CompanyDetail } from '@/types/api';

/** A company's profile (by EIK): stats, its flag history, and related (shell) companies. */
export function useCompany(eik: string) {
  return useQuery({
    queryKey: queryKeys.company(eik),
    queryFn: async () => {
      const response = await http.get<CompanyDetail>(`/companies/${eik}`);
      return response.data;
    },
    enabled: eik.length > 0,
  });
}
