import { LicenseInfo } from '@mui/x-license';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import { env } from '@/config/env';
import { logger } from '@/lib/logger';
import { registerServiceWorker } from '@/lib/pwa';
import { AppProviders } from '@/providers/AppProviders';
import { router } from '@/routes/router';
import './index.css';

// MUI X Premium key (optional — Community components work without it; a watermark shows if absent).
if (env.muiLicenseKey !== '') {
  LicenseInfo.setLicenseKey(env.muiLicenseKey);
}

/** Start the MSW worker before mounting so the very first request is already intercepted.
 *  Dynamically imported so the mock layer is code-split out of production builds. */
async function enableMocks(): Promise<void> {
  if (!env.enableMocks) return;
  const { worker } = await import('@/mocks/browser');
  await worker.start({ onUnhandledRequest: 'bypass' });
}

function mount(): void {
  const container = document.getElementById('root');
  if (container === null) throw new Error('Root element #root not found');
  createRoot(container).render(
    <StrictMode>
      <AppProviders>
        <RouterProvider router={router} future={{ v7_startTransition: true }} />
      </AppProviders>
    </StrictMode>,
  );
}

enableMocks()
  .catch((error: unknown) => {
    logger.error('mock_worker_failed', {
      message: error instanceof Error ? error.message : String(error),
    });
  })
  .finally(() => {
    mount();
    registerServiceWorker();
  });
