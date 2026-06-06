import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { Source } from '@/types/api';

export interface SourceFormValues {
  key: string;
  label: string;
  base_url: string;
  enabled: boolean;
  notes?: string;
}

export function useSources() {
  return useQuery({
    queryKey: queryKeys.sources(),
    queryFn: async (): Promise<Source[]> => (await http.get<Source[]>('/admin/sources')).data,
  });
}

function useInvalidateSources() {
  const queryClient = useQueryClient();
  return () => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.sources() });
  };
}

export function useCreateSource() {
  const invalidate = useInvalidateSources();
  return useMutation({
    mutationFn: async (input: SourceFormValues): Promise<Source> =>
      (await http.post<Source>('/admin/sources', input)).data,
    onSuccess: invalidate,
  });
}

export interface UpdateSourceInput {
  publicId: string;
  patch: Partial<SourceFormValues>;
}

export function useUpdateSource() {
  const invalidate = useInvalidateSources();
  return useMutation({
    mutationFn: async ({ publicId, patch }: UpdateSourceInput): Promise<Source> =>
      (await http.patch<Source>(`/admin/sources/${encodeURIComponent(publicId)}`, patch)).data,
    onSuccess: invalidate,
  });
}

export function useDeleteSource() {
  const invalidate = useInvalidateSources();
  return useMutation({
    mutationFn: async (publicId: string): Promise<void> => {
      await http.delete(`/admin/sources/${encodeURIComponent(publicId)}`);
    },
    onSuccess: invalidate,
  });
}
