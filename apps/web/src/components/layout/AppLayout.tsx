import { Box } from '@mui/material';
import { Outlet } from 'react-router-dom';
import { AppDemoBanner } from '@/components/feedback/AppDemoBanner';
import { AppErrorBoundary } from '@/components/feedback/AppErrorBoundary';
import { AppContainer } from './AppContainer';
import { AppFooter } from './AppFooter';
import { AppHeader } from './AppHeader';

/** The shared frame for every route: demo banner, header, the routed page (guarded by an
 *  error boundary), and the footer. Mobile-first column that fills the viewport height. */
export function AppLayout() {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100%' }}>
      <AppDemoBanner />
      <AppHeader />
      <Box component="main" sx={{ flex: 1, width: '100%' }}>
        <AppContainer sx={{ py: 4 }}>
          <AppErrorBoundary>
            <Outlet />
          </AppErrorBoundary>
        </AppContainer>
      </Box>
      <AppFooter />
    </Box>
  );
}
