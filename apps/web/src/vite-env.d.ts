/// <reference types="vite/client" />
/// <reference types="vite-plugin-pwa/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL: string;
  readonly VITE_APP_NAME: string;
  readonly VITE_MUI_X_LICENSE_KEY?: string;
  readonly VITE_ENABLE_MOCKS?: string;
  readonly VITE_SENTRY_DSN?: string;
  readonly VITE_ANALYTICS_DOMAIN?: string;
  readonly VITE_ANALYTICS_SRC?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
