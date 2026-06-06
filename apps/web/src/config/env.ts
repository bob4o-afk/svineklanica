/** Typed, validated access to the public build-time env. NOTE: every VITE_* var is
 *  PUBLIC (baked into the bundle) — never put a secret here. (.claude/rules/security) */

function bool(value: string | undefined, fallback: boolean): boolean {
  if (value === undefined) return fallback;
  return value === 'true' || value === '1';
}

export const env = {
  apiUrl: import.meta.env.VITE_API_URL ?? '/api',
  appName: import.meta.env.VITE_APP_NAME ?? 'Corruption Fucker',
  muiLicenseKey: import.meta.env.VITE_MUI_X_LICENSE_KEY ?? '',
  /** Mocks default ON in dev, OFF in prod, unless explicitly overridden. */
  enableMocks: bool(import.meta.env.VITE_ENABLE_MOCKS, import.meta.env.DEV),
  sentryDsn: import.meta.env.VITE_SENTRY_DSN ?? '',
  analyticsDomain: import.meta.env.VITE_ANALYTICS_DOMAIN ?? '',
  analyticsSrc: import.meta.env.VITE_ANALYTICS_SRC ?? '',
  isDev: import.meta.env.DEV,
} as const;

export type Env = typeof env;
