import { Box } from '@mui/material';
import { Outlet, ScrollRestoration } from 'react-router-dom';
import { AppErrorBoundary } from '@/components/feedback/AppErrorBoundary';
import { AppContainer } from './AppContainer';
import { AppFooter } from './AppFooter';
import { AppHeader } from './AppHeader';
import { AppInstallPrompt } from './AppInstallPrompt';
import { AppNavProgress } from './AppNavProgress';
import { AppWatermark } from './AppWatermark';

/** The shared frame for every route: header, the routed page (guarded by an
 *  error boundary), and the footer. Mobile-first column that fills the viewport height. */
export function AppLayout() {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100%', position: 'relative' }}>
      {/* Red top progress bar — flashes on navigation + while data loads. */}
      <AppNavProgress />
      {/* Scroll to top on every new navigation; restore position on back/forward. */}
      <ScrollRestoration />
      <AppWatermark />
      {/* Center-column mask: solid bg panel matching the content container width so the
          watermark is naturally visible in the left/right margins. box-shadow softens the
          hard edge. Fixed so it covers header/footer area too — both have their own
          solid backgrounds on a higher z-index so this never bleeds into them. */}
      <Box
        aria-hidden
        sx={(theme) => ({
          position: 'fixed',
          top: 0,
          bottom: 0,
          left: '50%',
          transform: 'translateX(-50%)',
          width: 'min(100%, 960px)',
          bgcolor: 'background.default',
          zIndex: 0,
          pointerEvents: 'none',
          boxShadow: `0 0 80px 40px ${theme.palette.background.default}`,
        })}
      />
      <AppHeader />
      <Box component="main" sx={{ flex: 1, width: '100%', position: 'relative', zIndex: 1 }}>
        <AppContainer sx={{ py: 4 }}>
          <AppErrorBoundary>
            <Outlet />
          </AppErrorBoundary>
        </AppContainer>
      </Box>
      <AppFooter />
      <AppInstallPrompt />
    </Box>
  );
}
