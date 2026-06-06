import { LicenseInfo } from '@mui/x-license';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import { env } from '@/config/env';
import { logger } from '@/lib/logger';
import { initMonitoring } from '@/lib/monitoring';
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

// Guards a one-time self-healing reload (see enableMocks). Per-tab, so a fresh session can retry.
const MSW_RELOAD_KEY = 'msw-control-reload';

/** Start the MSW worker and confirm it actually **controls** the page before mounting.
 *
 *  Returns `false` when it has kicked off a one-time reload — the caller must then NOT mount,
 *  because the reload re-runs this bootstrap.
 *
 *  Why this matters (the "data windows break on a code change" bug): in dev nearly every edit does
 *  a full reload, and right after a reload there's a race where `worker.start()` rejects or the
 *  service worker isn't controlling this page yet. A service worker only intercepts fetches for
 *  clients it controls — so if we mount anyway (the old `.finally(mount)`), every `/api/*` request
 *  bypasses MSW and falls through to the dev server's `index.html` SPA fallback. The result: all
 *  data windows error at once, and the in-app "Try again" (a refetch) hits the same un-intercepted
 *  path so it can't recover — only a manual reload does. We do that reload automatically instead.
 *  Dynamically imported so the mock layer is code-split out of production builds. */
async function enableMocks(): Promise<boolean> {
  if (!env.enableMocks) return true; // no mocks → nothing to wait for; mount normally

  function reloadOnce(): boolean {
    if (sessionStorage.getItem(MSW_RELOAD_KEY) === null) {
      sessionStorage.setItem(MSW_RELOAD_KEY, '1');
      window.location.reload();
      return false; // signal: do not mount; the reload takes over
    }
    return true; // already retried this episode — proceed rather than loop forever
  }

  try {
    const { worker } = await import('@/mocks/browser');
    await worker.start({ onUnhandledRequest: 'bypass' });
    await navigator.serviceWorker.ready;
    // If the (re)installed worker isn't controlling this load yet, MSW can't intercept → reload.
    if (navigator.serviceWorker.controller === null && !reloadOnce()) return false;
    sessionStorage.removeItem(MSW_RELOAD_KEY); // healthy → reset so a later lapse can self-heal too
    return true;
  } catch (error: unknown) {
    logger.error('mock_worker_failed', {
      message: error instanceof Error ? error.message : String(error),
    });
    // Mounting without mocks guarantees every /api call falls through to the SPA shell — reload to
    // retry the worker instead of showing a permanently broken app.
    return reloadOnce();
  }
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

void enableMocks()
  .then((ready) => {
    if (ready) mount();
  })
  .catch(() => mount()); // last resort: never leave a blank screen on an unexpected failure
