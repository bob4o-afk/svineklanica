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

## D. Dependency / security decisions (YOU decide — I left them as-is on purpose)
- [ ] **`vitest` critical advisory `GHSA-5xrq-8626-4rwp` (CVSS 9.8) — currently ignored.** It's in `package.json → pnpm.auditConfig.ignoreGhsas`. **Why it's fine for the demo:** vitest is a dev/test dependency, *not* shipped in the production bundle, so it's not a runtime exposure. The fix is `vitest 2.1.9 → 4.1.0` — a **major** bump that could break the 54-test suite the day before the demo. **Decision:** keep ignored for the demo; schedule the vitest 4 upgrade for after. _(If you want, I can attempt the upgrade on a throwaway branch and report whether the suite survives.)_
- [ ] **2 moderate dev-server advisories** (`esbuild` GHSA-67mh-4wv8-2f99, `vite` path-traversal GHSA-4w7w-66w2-5vf9). Only exploitable while `pnpm dev` is running *and* a malicious site is open in the same browser — irrelevant to the built/deployed demo. Clearing them needs a **vite 5 → 6 major** bump (risk to the PWA plugin + build). **Decision:** defer; not a production risk.
- [ ] **License still undecided (GPL-3.0 vs MIT)** — `LICENSE` is GPL-3.0 but `CLAUDE.md §2.5` flags this as an open team decision. OSS license is **mandatory before the demo** (hackathon rule). Pick one and make the `CLAUDE.md` line match `LICENSE`.

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
