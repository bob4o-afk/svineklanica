import { Box, LinearProgress } from '@mui/material';
import { useIsFetching, useIsMutating } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { palette } from '@/theme/tokens';

/** Thin red progress bar pinned to the very top of the viewport (leha-style). Shows while
 *  navigating (flashes on every route change, even to a cached page) and while any react-query
 *  fetch/mutation is in flight — so a redirect or a data load always has a visible cue. */
export function AppNavProgress() {
  const fetching = useIsFetching();
  const mutating = useIsMutating();
  const location = useLocation();
  const [navPending, setNavPending] = useState(false);

  // Flash the bar on each navigation so even an instant (cached) redirect shows movement.
  useEffect(() => {
    setNavPending(true);
    const timer = window.setTimeout(() => setNavPending(false), 500);
    return () => window.clearTimeout(timer);
  }, [location.key]);

  const active = navPending || fetching > 0 || mutating > 0;

  return (
    <Box
      aria-hidden
      sx={(theme) => ({
        position: 'fixed',
        insetInline: 0,
        top: 0,
        height: 3,
        zIndex: theme.zIndex.appBar + 2,
        pointerEvents: 'none',
      })}
    >
      {active ? (
        <LinearProgress
          sx={{
            height: 3,
            backgroundColor: 'transparent',
            '& .MuiLinearProgress-bar': { backgroundColor: palette.alarm },
          }}
        />
      ) : null}
    </Box>
  );
}
