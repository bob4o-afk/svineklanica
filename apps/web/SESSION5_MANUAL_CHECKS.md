# Session 5 — manual checks & decisions for the human

> Everything Session 5 touched (flagship-viz QA + fixes + a security/health pass), plus the
> decisions only you can make. Automated gates already pass (`typecheck · lint · 54 vitest ·
> build · pnpm audit --audit-level=high (exit 0)`) and the branch-diff security review found
> nothing — this list is the **eyeball + decide** layer on top.

**Run:** `corepack pnpm -C apps/web dev` → open the printed `localhost` URL (it may not be `:5173`
if that port is busy — read the line Vite prints).
**Theme toggle:** the sun/moon button in the header (`Към светла/тъмна тема`).
> If `/api/*` 404s after an edit: stale service worker → DevTools ▸ Application ▸ unregister, hard-reload.

---

## A. Price chart — `/price/laptops` (verify in LIGHT **and** DARK)
- [ ] **Y-axis labels are all distinct** — `0 лв · 1 хил. лв · 2 хил. лв · 3 хил. лв · 4 хил. лв · 5 хил. лв`. (The old bug showed "2 хил. лв" and "3 хил. лв" twice.)
- [ ] **Last x-axis date `11 май 2026` is fully visible** (not clipped at the right edge).
- [ ] **Outlier highlight renders:** the spike at **25 фев 2026** has a contrasting dot (black in light / bone in dark), a dashed vertical line through it, and the label **„Извънредна цена"** at the top.
- [ ] The line tells the overpricing story: rises to the spike, drops, then creeps up.
- [ ] Hovering a point shows a tooltip with the formatted price; hovering the spike also shows the „Извънредна цена" series.
- [ ] Bonus — error state: open `/price/does-not-exist` → „Визуализацията не се зареди" + „Опитай пак" button (no blank screen).

## B. Serial-winner graph — `/network/comp-1` (verify in LIGHT **and** DARK)
- [ ] **DARK mode is the important one:** the zoom **Controls (＋ − ⤢) bottom-left are clearly visible** (they were invisible white-on-dark before), the „React Flow" attribution bottom-right is legible, and the edge-label pills („6 поръчки" / „4 поръчки") match the dark UI.
- [ ] Two red company nodes (with win counts ● 9 / ● 4), two bordered authority nodes, dashed red edges between them.
- [ ] Pan/zoom/fit work; the graph is centered (fitView).

## C. Region map — `/map` (regression — wasn't changed, just confirm still good)
- [ ] Light + dark both render the Bulgaria choropleth shaded by flag count.
- [ ] Hover a province → tooltip „<Област> · N сигнала".
- [ ] Click a shaded province → grows, map fades, lands on `/feed?region=…` with that region's feed (e.g. Плевен → `BG314`).
- [ ] The sector filter chips at the top re-shade the map.

## C2. PWA install (Phase 5) — best on a real phone / a `pnpm build && pnpm preview`
> The PWA service worker is **disabled in `dev`** (it fights MSW) — to truly test install, run
> `corepack pnpm -C apps/web build && corepack pnpm -C apps/web preview` and open the preview URL.
- [ ] **Desktop Chrome/Edge:** an install icon appears in the address bar → install → it opens in its own window with the eye logo, name „СВИНЕКЛАННИЦА".
- [ ] **Android Chrome:** „Add to Home screen" / install banner; the home-screen icon is the **maskable** eye (fills the adaptive shape, not letterboxed).
- [ ] **iOS Safari:** an in-app hint banner „Инсталирай Свинекланица" with the Share glyph appears automatically (iOS has no native prompt); following it — Share ▸ Add to Home Screen → the home icon is the eye (apple-touch-icon), opens fullscreen.
- [ ] **In-app banner:** on an install-eligible browser a bottom banel „Инсталирай Свинекланица" shows with an „Инсталирай" button + „×" dismiss; dismiss hides it and it doesn't nag again (localStorage). Verified both light/dark already.
- [ ] Icons regenerate cleanly if the logo changes: `corepack pnpm -C apps/web gen:icons` (uses the `sharp` devDep) → updates `public/pwa-*.png` + `apple-touch-icon-180x180.png`.

## C3. Sentry error monitoring (Phase 5) — off by default, decision to enable
- [ ] It's a **no-op unless `VITE_SENTRY_DSN` is set at _build_ time** (Vite bakes VITE_* at build). With no DSN, `@sentry/react` is **fully tree-shaken out** (0 bytes) — confirmed in the build.
- [ ] **To enable:** set `VITE_SENTRY_DSN=<dsn>` before `pnpm build`, AND add the Sentry ingest host (e.g. `https://*.ingest.sentry.io`) to the prod CSP **`connect-src`** (in `vite.config.ts` `PROD_CSP` + the Caddy/nginx headers) — otherwise the browser blocks the report. **Decision:** enable only if you want live error capture for the demo; otherwise leave off.
- [ ] Every `logger.error` (incl. the React error boundary) forwards to Sentry once enabled.

## D. Dependency / security — RESOLVED this session ✅
- [x] **License = GPL-3.0 (DECIDED).** `LICENSE` (GPLv3) + `apps/web/package.json` (`GPL-3.0-or-later`) + README §License + `CLAUDE.md` aligned. ⚠️ The **root `composer.json`** (backend lane — untouched) should get its `license` field set to match when backend is next worked on.
- [x] **All 3 dependency advisories FIXED (validated on a throwaway worktree first).** Upgraded **`vitest 2.1.9 → 4.1.8`** (clears the critical `GHSA-5xrq-8626-4rwp`, CVSS 9.8) and **`vite 5.4.21 → 8.0.16`** + `@vitejs/plugin-react 6` + `vite-plugin-pwa 1.3.0` (clears the 2 moderate esbuild/vite advisories). **`pnpm audit` now reports „No known vulnerabilities found" (exit 0)** with NO ignores — the `pnpm.auditConfig.ignoreGhsas` entry was removed. All gates green: typecheck · lint · **60 vitest** · build · dev+MSW (vite 8 uses Rolldown → build ~2.4s). _Verify on your machine after pulling: `corepack pnpm -C apps/web install` then `… audit` → should be clean._
## E. Things only you can do (agent is blocked on these)
- [ ] **Commit + push Session 5.** `git push` is gated for the agent. When ready:
      `git add -A && git commit -m "fix(web): flagship-viz QA fixes + hygiene"` then `! git push origin frontend-viz:main`.
      The commit will also clean up the 14 accidentally-committed `.playwright-mcp/` files and the stray `proxy_log.txt` (both now deleted + gitignored — confirm you're happy with that before committing).
- [ ] **Confirm the demo flow on a phone width** (DevTools device toolbar): the three viz screens and the admin area stay usable, no horizontal overflow.

## F. Known caveats — EXPECTED, do NOT "fix" (still true from Phase 4)
- The admin MSW store is **in-memory**: approvals/source edits survive SPA navigation but **reset on a hard reload** (and a reload logs the editor out). For the live „watch it publish" demo, navigate within the SPA — don't F5. Goes away once the real backend is wired.
- `GET /api/admin/me` logs a **401 on every public page** — the normal "not an admin" probe, harmless in dev.
- The map is a **d3-geo choropleth, not Mapbox** — a deliberate offline/demo-safe choice (`frontend.md §10` says Mapbox; revisit only if you want a live basemap + token).

---

_Built on MSW — real data is still blocked cross-lane on the backend shipping the `API_SEAM.md`
endpoints. See `docs/BUILD_PLAN.md` ▸ "CURRENT STATUS & HANDOFF" for the next moves._
