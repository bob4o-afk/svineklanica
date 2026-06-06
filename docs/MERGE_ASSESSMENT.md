# Merge assessment — `feat/connecting-backend-frontend` → `main`

> Snapshot taken **2026-06-06** by an AI agent (read-only investigation; nothing was merged/committed).
> SHAs are point-in-time — re-run the verification command below before relying on this if branches have moved.

## TL;DR

**Project standing:** ~90% of `BUILD_PLAN.md` is built, but the integrated trunk (`origin/main`)
is **not running on real data end-to-end**. The piece that connects everything — the red-flag
**detectors** + the **FE↔BE seam API** (`API_SEAM.md`) — is **built but unmerged** on
`feat/connecting-backend-frontend`. Merging it is the single highest-payoff move.

**Merge severity: EASY.** Sub-hour job. Only **2 files conflict, both frontend**; the entire
backend payload (Presentation + Detection modules, all detectors, all migrations) **auto-merges clean**.

## Branches / SHAs at time of assessment
- `origin/main` tip: **`3777e8c`** (has: FE on MSW, scraper+embeddings, new `apps/ai` LLM layer, sphere-flows, generic posts API — **but NO detectors, NO seam endpoints**; the backend API wiring was deliberately removed before the sphere merge in `9ade2f4`).
- `feat/connecting-backend-frontend` tip: **`89fa549`** (has: 3 detectors [PriceDiscrepancy, SerialWinner, CancelledTender], full Detection module, the `Presentation` module = `API_SEAM.md` endpoints, sphere/category/score migrations on `flags`, FlagPost contract wiring).
- merge-base: **`ad5e27f`** (connecting forked before admin/PWA/viz/AI/sphere landed → 10 commits behind, but still merges clean).

## How to re-verify (read-only, no working-tree change)
```bash
git fetch --all
git merge-tree --write-tree --name-only origin/main origin/feat/connecting-backend-frontend
# line 1 = result tree oid; any lines before the "Auto-merging..." block = conflicted paths
```

## The 2 conflicts

### 1. `apps/web/src/types/contract.ts` — trivial (~2 min)
Both sides only **add** fields in adjacent spots:
- main: `PunkTag` type + `tags?` on `FlagPost` and `ReviewDecision`
- connecting: `view_count?` on `FlagPost` + a new `PlatformStats` interface

**Resolution:** keep both. No logic to reconcile.

### 2. `apps/web/src/main.tsx` — a decision, not a tangle (~10–15 min)
One hunk; the sides intend opposite things:
- **main**: keep MSW + a self-healing reload, and add Sentry `initMonitoring()`
- **connecting**: **remove MSW entirely**, boot against the real backend via the Vite `/api` proxy

This is exactly what the connecting branch exists to do (flip to real data), so it doesn't textually merge.

**Resolution:** take connecting's `bootstrap()`, **re-graft main's `initMonitoring()` line** (Sentry is
orthogonal — keep it), and consciously drop the now-moot MSW logic. The dev makes this call anyway as
part of "flip to real data," so it's not extra work.

## Non-textual risks — checked, ALL CLEAR
- **No duplicate-migration / duplicate-column failure at `migrate` time.** main's `tenders` table has
  only `cpv_code` (no `sphere`/`category`); main never modified `create_flags_table.php` since the
  fork; connecting's new ALTER migrations
  (`add_sphere_category_to_tenders_table`, `add_presentation_columns_to_flags`,
  `add_core_model_columns_to_flags`, `add_view_count_to_flags`) have unique filenames and apply cleanly on top.

## Post-merge checklist (git won't flag these)
- [ ] **Service-worker double-registration:** main ships PWA via `vite-plugin-pwa` (auto SW register);
      connecting adds its own `lib/pwa.ts` with manual `registerServiceWorker()`. Reconcile so prod
      doesn't register twice. (~2 min)
- [ ] Run migrations on a fresh DB → `ingest:run` the committed TED sample
      (`storage/ingest/samples/ted.ndjson`, 10 records) → run the detectors → confirm
      `GET /api/flag-posts` serves **real** flags.
- [ ] `composer sync:api-types` → reconcile `apps/web/src/types/generated.d.ts` vs `contract.ts`,
      set `VITE_ENABLE_MOCKS=false` + `VITE_API_URL`, verify cookie/CORS/CSP `connect-src`.
- [ ] Backend `composer.json` license field → align to GPL-3.0 (other lanes already aligned).
