import { logger } from '@/lib/logger';

// How often an already-open tab re-checks the server for a newer service worker.
// The `no-cache` header on `sw.js` (nginx.conf) makes each check actually hit the
// server instead of a stale cached worker, so a long-lived session notices a fresh
// deploy within this window rather than only on a manual refresh.
const UPDATE_CHECK_INTERVAL_MS = 60_000;

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

      // Poll for a newer build while the tab stays open.
      window.setInterval(() => {
        void registration.update();
      }, UPDATE_CHECK_INTERVAL_MS);

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
