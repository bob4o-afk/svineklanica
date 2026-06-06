import { env } from '@/config/env';

type Capture = (message: string, context?: Record<string, unknown>) => void;

let capture: Capture | null = null;

/** Initialize remote error monitoring (Sentry). A no-op unless `VITE_SENTRY_DSN` is set, so the
 *  demo/dev default ships nothing — and `@sentry/react` is **dynamically imported** so it's
 *  code-split out of any build without a DSN. Call once at app bootstrap (main.tsx). */
export async function initMonitoring(): Promise<void> {
  if (env.sentryDsn === '') return;
  const Sentry = await import('@sentry/react');
  Sentry.init({
    dsn: env.sentryDsn,
    environment: env.isDev ? 'development' : 'production',
    tracesSampleRate: 0, // errors only for now — no performance tracing
  });
  capture = (message, context) => {
    Sentry.captureMessage(
      message,
      context === undefined ? { level: 'error' } : { level: 'error', extra: context },
    );
  };
}

/** Forward an error to remote monitoring when initialized; a no-op otherwise. Wired into
 *  `logger.error` so every logged error (incl. the React error boundary) is reported. */
export function captureError(message: string, context?: Record<string, unknown>): void {
  if (capture !== null) capture(message, context);
}
