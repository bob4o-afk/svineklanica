import { defineConfig, type Plugin } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'node:path';
import { BRAND } from './src/config/brand';
import { palette } from './src/theme/tokens';

// The production CSP — strict: no `'unsafe-inline'` on script-src and no `ws:` (those are dev-only
// Vite needs). 'unsafe-inline' stays on style-src because Emotion injects <style> at runtime.
// The <meta> in index.html carries the looser DEV policy; this swaps in the tight one at build.
// Real HTTP headers (incl. frame-ancestors + HSTS) land in Caddy/nginx in Phase 5.
const PROD_CSP = [
  "default-src 'self'",
  "script-src 'self'",
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data:",
  "font-src 'self'",
  "connect-src 'self'",
  "worker-src 'self' blob:",
  "manifest-src 'self'",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  "frame-ancestors 'none'",
  'upgrade-insecure-requests',
].join('; ');

function prodCspPlugin(): Plugin {
  return {
    name: 'cf-prod-csp',
    apply: 'build',
    transformIndexHtml(html) {
      return html.replace(
        /(http-equiv="Content-Security-Policy"\s+content=")[^"]*(")/,
        `$1${PROD_CSP}$2`,
      );
    },
  };
}

// "Mobile" = this same app as an installable, mobile-first PWA. (frontend.md §0)
export default defineConfig({
  plugins: [
    react(),
    prodCspPlugin(),
    VitePWA({
      registerType: 'autoUpdate',
      // We register the SW ourselves from app code (src/lib/pwa.ts) — that's what lets us
      // add a periodic update check so open tabs auto-reload onto a fresh deploy. `false`
      // stops the plugin from ALSO injecting its own registration (which would
      // double-register). The registration code is bundled into the app's hashed JS, not an
      // inline <script>, so the strict prod `script-src 'self'` CSP still holds.
      injectRegister: false,
      // Never build/serve the PWA service worker in dev — pwa.ts registers /sw.js
      // unconditionally, so without this the SW would fight MSW's mock worker for control
      // of the page and make /api/* requests fall through (404). PWA is a production concern.
      devOptions: { enabled: false },
      includeAssets: ['favicon.svg', 'robots.txt'],
      manifest: {
        name: BRAND.name,
        short_name: BRAND.short,
        description: 'Обществените поръчки на показ — граждански инструмент срещу корупцията.',
        lang: 'bg',
        theme_color: palette.ink,
        background_color: palette.ink,
        display: 'standalone',
        start_url: '/',
        // SVG icon scales to every size; PNG/maskable set is a Phase-5 polish.
        icons: [{ src: 'favicon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any' }],
      },
    }),
  ],
  resolve: {
    alias: { '@': path.resolve(__dirname, './src') },
  },
  server: {
    host: true,
    port: 5173,
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
  },
});
