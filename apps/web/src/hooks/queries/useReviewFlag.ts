import { useMutation, useQueryClient } from '@tanstack/react-query';
import { http } from '@/lib/http';
import { queryKeys } from '@/lib/queryKeys';
import type { FlagPost, ReviewDecision } from '@/types/api';

/** Invalidate everything the public feed/maps and the review queue derive from a flag's state, so
 *  an approval shows up live on the citizen side and drops out of the queue. */
function useInvalidateAfterReview() {
  const queryClient = useQueryClient();
  return () => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.pendingFlags() });
    void queryClient.invalidateQueries({ queryKey: ['flag-feed'] });
    void queryClient.invalidateQueries({ queryKey: ['region-aggregate'] });
  };
}

export interface ApproveInput {
  publicId: string;
  decision: ReviewDecision;
}

/** Publish a flag (with the editor's edits + punk tags). */
export function useApproveFlag() {
  const invalidate = useInvalidateAfterReview();
  return useMutation({
    mutationFn: async ({ publicId, decision }: ApproveInput): Promise<FlagPost> => {
      const response = await http.post<FlagPost>(
        `/admin/flag-posts/${encodeURIComponent(publicId)}/approve`,
        decision,
      );
      return response.data;
    },
    onSuccess: invalidate,
  });
}

/** Reject a flag — it leaves the queue and never reaches the public feed. */
export function useRejectFlag() {
  const invalidate = useInvalidateAfterReview();
  return useMutation({
    mutationFn: async (publicId: string): Promise<FlagPost> => {
      const response = await http.post<FlagPost>(
        `/admin/flag-posts/${encodeURIComponent(publicId)}/reject`,
        {},
      );
      return response.data;
    },
    onSuccess: invalidate,
  });
}
