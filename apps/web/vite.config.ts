// defineConfig from vitest/config (a superset of vite's) so the `test` block below
// type-checks; the Vite plugin/config types are otherwise identical.
import { defineConfig } from 'vitest/config';
import type { Plugin } from 'vite';
  import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { BRAND } from './src/config/brand';
import { palette } from './src/theme/tokens';

// Public site origin (matches public/sitemap.xml + robots.txt). Change if the domain differs.
const SITE_URL = 'https://bobinkata.com';

// Schema.org JSON-LD (Organization + WebSite + SearchAction) → richer Google results / sitelinks /
// search box. Injected into <head> as <script type="application/ld+json"> by structuredDataPlugin.
// It's a single fixed string so its hash is stable; that hash is allow-listed in the prod CSP
// below (dev allows it via 'unsafe-inline'). Edit this string and the hash updates automatically.
const STRUCTURED_DATA = JSON.stringify({
  '@context': 'https://schema.org',
  '@graph': [
    {
      '@type': 'Organization',
      '@id': `${SITE_URL}/#organization`,
      name: BRAND.name,
      alternateName: 'Svineklanitsa Watchdog',
      url: `${SITE_URL}/`,
      description:
        'Парите са обществени. Прозрачността — не. Откриваме съмнителни обществени поръчки и плащания в България и показваме къде отиват парите ти.',
      logo: `${SITE_URL}/pwa-512x512.png`,
      sameAs: [BRAND.socials.instagram, BRAND.socials.github],
    },
    {
      '@type': 'WebSite',
      '@id': `${SITE_URL}/#website`,
      url: `${SITE_URL}/`,
      name: BRAND.name,
      inLanguage: 'bg',
      publisher: { '@id': `${SITE_URL}/#organization` },
      potentialAction: {
        '@type': 'SearchAction',
        target: { '@type': 'EntryPoint', urlTemplate: `${SITE_URL}/search?q={search_term_string}` },
        'query-input': 'required name=search_term_string',
      },
    },
  ],
});

// The browser hashes the EXACT text content of the inline <script>, so hashing the same string we
// inject guarantees the CSP allow-list matches byte-for-byte.
const STRUCTURED_DATA_SHA256 = createHash('sha256').update(STRUCTURED_DATA, 'utf8').digest('base64');

// The production CSP — strict: no `'unsafe-inline'` on script-src and no `ws:` (those are dev-only
// Vite needs). 'unsafe-inline' stays on style-src because Emotion injects <style> at runtime.
// The <meta> in index.html carries the looser DEV policy; this swaps in the tight one at build.
// Real HTTP headers (incl. frame-ancestors + HSTS) land in Caddy/nginx in Phase 5.
// NB: `frame-ancestors` is intentionally NOT here — browsers IGNORE it when CSP is delivered via
// a <meta> tag (it's only valid in a real HTTP header), so it just logs a console warning. Framing
// is blocked by the `X-Frame-Options: DENY` header Caddy already sends (security.md §8); the real
// prod CSP header (Caddy/nginx) is where `frame-ancestors 'none'` belongs.
const PROD_CSP = [
  "default-src 'self'",
  // Allow our own scripts + the one inline JSON-LD block (by hash — no 'unsafe-inline').
  `script-src 'self' 'sha256-${STRUCTURED_DATA_SHA256}'`,
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data:",
  // `data:` so inline/base64 font faces (e.g. @fontsource latin-ext subsets) aren't blocked.
  "font-src 'self' data:",
  "connect-src 'self'",
  "worker-src 'self' blob:",
  "manifest-src 'self'",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
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

/** Inject the Schema.org JSON-LD into <head> (dev + build). Its hash is allow-listed in PROD_CSP;
 *  in dev the meta CSP's 'unsafe-inline' covers it. Children is the exact hashed string. */
function structuredDataPlugin(): Plugin {
  return {
    name: 'cf-structured-data',
    transformIndexHtml() {
      return [
        {
          tag: 'script',
          attrs: { type: 'application/ld+json' },
          children: STRUCTURED_DATA,
          injectTo: 'head',
        },
      ];
    },
  };
}

// "Mobile" = this same app as an installable, mobile-first PWA. (frontend.md §0)
export default defineConfig({
  plugins: [
    react(),
    prodCspPlugin(),
    structuredDataPlugin(),
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
      includeAssets: ['favicon.svg', 'robots.txt', 'apple-touch-icon-180x180.png'],
      manifest: {
        name: BRAND.name,
        short_name: BRAND.short,
        description: 'Обществените поръчки на показ — граждански инструмент срещу корупцията.',
        lang: 'bg',
        theme_color: palette.ink,
        background_color: palette.ink,
        display: 'standalone',
        start_url: '/',
        // Raster icons (generated from favicon.svg via `pnpm gen:icons`) for installability +
        // a maskable variant for Android adaptive icons; the SVG stays as the scalable fallback.
        icons: [
          { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
          { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
          { src: 'pwa-maskable-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
          { src: 'favicon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any' },
        ],
      },
    }),
  ],
  resolve: {
    alias: { '@': path.resolve(__dirname, './src') },
  },
  server: {
    host: true,
    port: 5173,
    // Vite 5.4.12+/8 blocks requests whose Host header isn't allow-listed (DNS-rebinding guard),
    // returning 403. Caddy proxies https://localhost preserving Host: localhost (allowed), but
    // the `web` service name and any custom APP_DOMAIN must be listed explicitly or the proxied
    // request 403s ("not loading"). Override/extend via VITE_ALLOWED_HOSTS (comma-separated).
    allowedHosts: (process.env.VITE_ALLOWED_HOSTS ?? `localhost,web,${process.env.APP_DOMAIN ?? ''}`)
      .split(',')
      .map((host) => host.trim())
      .filter((host) => host !== ''),
    // Docker Desktop on Windows doesn't forward host filesystem (inotify) events into the Linux
    // container, so Vite's watcher won't see edits and HMR won't fire automatically — a manual
    // reload still picks up changes (Vite reads from disk per request). Opt into polling for live
    // HMR by setting VITE_USE_POLLING=true; it's left OFF by default because aggressive polling
    // over the slow Windows bind mount pins a CPU core and can stall the dev server.
    ...(process.env.VITE_USE_POLLING === 'true'
      ? { watch: { usePolling: true, interval: 1000, binaryInterval: 1500 } }
      : {}),
    // Direct http://localhost:5173 access now talks to the REAL backend (the mock layer is
    // gone). Caddy already routes /api → app for the canonical https://localhost URL; this
    // proxy gives the same reach when hitting Vite directly. Target is the app service on the
    // compose network (override with VITE_API_PROXY for non-Docker dev).
    proxy: {
      '/api': { target: process.env.VITE_API_PROXY ?? 'http://app:8000', changeOrigin: true },
      '/sanctum': { target: process.env.VITE_API_PROXY ?? 'http://app:8000', changeOrigin: true },
      '/_health': { target: process.env.VITE_API_PROXY ?? 'http://app:8000', changeOrigin: true },
    },
    // The canonical dev URL is https://localhost (the Caddy TLS proxy). The HMR websocket
    // must therefore go back through Caddy on :443 as wss — otherwise the client tries
    // wss://localhost:5173 (plain HTTP, no TLS) and HMR silently dies, leaving the page
    // feeling broken/stale. Caddy's reverse_proxy upgrades the ws transparently.
    // Set VITE_HMR_DIRECT=true to instead get native HMR for direct http://localhost:5173
    // access (in that mode https HMR won't connect — pick one entry point per dev server).
    hmr:
      process.env.VITE_HMR_DIRECT === 'true'
        ? { host: 'localhost', port: 5173 }
        : { protocol: 'wss', host: 'localhost', clientPort: 443 },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
  },
});
