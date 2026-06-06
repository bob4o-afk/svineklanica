import { Skeleton, Stack } from '@mui/material';

export interface AppSkeletonProps {
  /** How many stacked skeleton blocks to render (e.g. a list of cards). */
  count?: number;
  /** Height of each block in px. */
  height?: number;
}

/** Loading placeholder. Every async view shows one of these instead of a frozen screen
 *  (frontend.md §7). */
export function AppSkeleton({ count = 3, height = 120 }: AppSkeletonProps) {
  return (
    <Stack spacing={2} aria-busy="true" aria-live="polite">
      {Array.from({ length: count }, (_, i) => (
        <Skeleton key={i} variant="rounded" height={height} animation="wave" />
      ))}
    </Stack>
  );
}
