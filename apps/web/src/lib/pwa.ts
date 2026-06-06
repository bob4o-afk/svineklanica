import { logger } from '@/lib/logger';

// Fallback poll for an idle, focused tab that never fires a visibility/focus event
// (kept short so even a left-open dashboard lands on a fresh deploy within seconds).
// The real-time path is event-driven (checkForUpdate below) — this is just a safety net.
// The `no-cache` header on `sw.js` (nginx.conf) makes each check actually hit the
// server instead of a stale cached worker, so the check sees a fresh deploy.
const UPDATE_CHECK_INTERVAL_MS = 20_000;

/** Register the PWA service worker and keep open tabs current with the latest deploy.
 *  Lives in the app bundle (not an inline <script>), so the strict prod CSP
 *  `script-src 'self'` still holds. */
export function registerServiceWorker(): void {
  if (!('serviceWorker' in navigator)) return;

  let reloading = false;

  void navigator.serviceWorker
    .register('/sw.js', { scope: '/' })
    .then((registration) => {
      logger.info('sw_registered', { scope: registration.scope });

      // Ask the browser to fetch /sw.js and compare it to the active worker. If a new
      // deploy is live, this is what discovers it (then the updatefound path below
      // activates + reloads). Cheap + safe to call often — a no-op when nothing changed.
      const checkForUpdate = (): void => {
        void registration.update();
      };

      // Instant on deploy: re-check the moment the user is actually looking at the tab
      // (open it, alt-tab back, refocus the window) or reconnects — not just on a timer.
      // This is what turns a normal reload / tab-refocus into a fresh version with no
      // hard refresh. `load` covers a returning visitor whose cached SW served the page.
      checkForUpdate();
      window.addEventListener('load', checkForUpdate);
      window.addEventListener('focus', checkForUpdate);
      window.addEventListener('online', checkForUpdate);
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') checkForUpdate();
      });

      // Safety-net poll for a tab that stays open and focused without firing the events above.
      window.setInterval(checkForUpdate, UPDATE_CHECK_INTERVAL_MS);

      // The SW is built with skipWaiting + clientsClaim (registerType: 'autoUpdate'),
      // so a new build activates and takes control on its own. When that happens to a
      // page that was ALREADY controlled — i.e. a real update, not the first install —
      // reload once to swap in the fresh app shell.
      registration.addEventListener('updatefound', () => {
        const incoming = registration.installing;
        if (incoming === null) return;
        const isUpdate = navigator.serviceWorker.controller !== null;
        incoming.addEventListener('statechange', () => {
          if (isUpdate && !reloading && incoming.state === 'activated') {
            reloading = true;
            window.location.reload();
          }
        });
      });
    })
    .catch((error: unknown) => {
      logger.error('sw_register_failed', {
        message: error instanceof Error ? error.message : String(error),
      });
    });
}

/** Tear down any service worker left over from a previous session (or a prod build
 *  opened on the same origin). A stale SW keeps controlling the page and intercepts
 *  every request — including MSW's /mockServiceWorker.js and /api/* — returning 404.
 *  Dev-only cleanup; if one was still controlling this page, reload once so the page
 *  comes up uncontrolled. */
export async function unregisterServiceWorkers(): Promise<void> {
  if (!('serviceWorker' in navigator)) return;

  const registrations = await navigator.serviceWorker.getRegistrations();
  if (registrations.length === 0) return;

  await Promise.all(registrations.map((registration) => registration.unregister()));
  logger.info('sw_unregistered_stale', { count: registrations.length });

  // The old worker still owns this client until the page reloads.
  if (navigator.serviceWorker.controller !== null) {
    window.location.reload();
  }
}
