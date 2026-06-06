import { LicenseInfo } from '@mui/x-license';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import { env } from '@/config/env';
import { logger } from '@/lib/logger';
import { initMonitoring } from '@/lib/monitoring';
import { registerServiceWorker, unregisterServiceWorkers } from '@/lib/pwa';
import { AppProviders } from '@/providers/AppProviders';
import { router } from '@/routes/router';
import './index.css';

// MUI X Premium key (optional — Community components work without it; a watermark shows if absent).
if (env.muiLicenseKey !== '') {
  LicenseInfo.setLicenseKey(env.muiLicenseKey);
}

// Remote error monitoring (Sentry) — no-op unless VITE_SENTRY_DSN is set (frontend.md §4).
void initMonitoring().catch((error: unknown) => {
  logger.error('monitoring_init_failed', {
    message: error instanceof Error ? error.message : String(error),
  });
});

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

async function bootstrap(): Promise<void> {
  // Dev: evict any service worker from a previous session — a stale SW would intercept
  // /api/* and 404 them. The app talks to the real backend (via the Vite /api proxy in
  // dev, or Caddy), so there is no mock layer to start. (May reload once if one was
  // still controlling the page.)
  if (import.meta.env.DEV) {
    await unregisterServiceWorkers();
  }

  mount();

  // PWA is a production-only concern (frontend.md §0) — never register a SW in dev.
  if (import.meta.env.PROD) {
    registerServiceWorker();
  }
}

void bootstrap();
