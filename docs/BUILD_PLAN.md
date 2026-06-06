# Build plan — maximum scope, prod-ready, without bombing efficiency

> Goal: **ship a prod-ready tool that covers as many bases as possible in ~48h** — without the scope sinking us (our known weak spot). The method is simple: **rank everything by payoff-per-hour and take the best ratio first.** Stop adding when the demo path is solid; everything past that is gravy.
>
> Two facts make "prod-ready + big scope" realistic:
> 1. **The prod infra is already scaffolded** (Docker, CI, Caddy TLS, health checks, prod compose, K8s, monitoring). Prod-readiness is mostly *flipping on* what's already here — cheap.
> 2. **"Biggest scope" = go deep+wide WITHIN procurement** (more sources, more detectors, more polish), **not** spreading across many corruption domains. Depth in one vertical reads as "it works"; breadth across five reads as "nothing works." (See `IDEA.md` Decision 1.)

---

# ▶ CURRENT STATUS & HANDOFF (updated 2026-06-06)

_Read this first. It's the live picture so a new agent can continue with minimal instructions. The tiered ranking below (Tier 0–4) is the longer-term reference._

## Where we are — `origin/main` @ `ad5e27f`; Admin Phase 4 built on `frontend-viz` (uncommitted)
**Frontend gates:** `typecheck · lint · 46 vitest · build · pnpm audit --audit-level=high` all pass.
> Admin Phase 4 (below) is in the working tree on `frontend-viz`, **not yet committed/pushed** — `git push` is gated, the human pushes (`! git push origin frontend-viz:main`). Manual QA checklist for it: [`apps/web/ADMIN_PHASE4_CHECKLIST.md`](../apps/web/ADMIN_PHASE4_CHECKLIST.md).

### ✅ Frontend (`apps/web`) — shipped, runs entirely on MSW mocks
Stack: React 19 + TS (strict, `exactOptionalPropertyTypes`) + MUI v6/MUI X v7 + Tailwind (preflight off, `important:'#root'`) + React Router v6 (data router) + TanStack Query v5 + i18next (BG-first) + Vitest. Mocks are **env-gated** (`VITE_ENABLE_MOCKS`, default on in dev).
- **Feed** (`/feed`): infinite scroll, faceted filters (type · severity · **sector**), sort; region drill-in.
- **Post** (`/posts/:id`): TL;DR, sourced explanation, evidence, subject cross-links, price/network links.
- **Entities**: authority (`/authorities/:id`) + company (`/companies/:eik`) with flag-history grids + shell `related`.
- **Search** (`/search`), **Home** hero.
- **Phase 3 viz** (all reachable): price-over-time chart (`/price/:seriesKey`, MUI X), serial-winner graph (`/network/:publicId`, React Flow), **corruption-by-region map** (`/map`, d3-geo choropleth on GISCO NUTS3) with a **sector filter** + click-to-expand → region feed (with feed **prefetch** during the animation).
- **Sector categories** (училище/болница/път…) CPV-derived (`lib/sectors.ts`); dark/light; themed scrollbar; scroll-to-top; favicon.
- **Admin (Phase 4) — SHIPPED on MSW** (`/admin/*`): real Sanctum SPA-cookie auth (`useMe` → `AuthProvider`, `useLogin`/`useLogout`), `ProtectedRoute` + `AdminLayout` (tabs + logout), **login**, **dashboard** (live pending/sources counts), **review queue** (`AdminDataGrid`), **ReviewPanel** (verify sources + edit title/explanation + assign **punk tags** + approve/reject), **Sources CRUD** (grid + `AppDialog` add/edit + enable toggle + delete). Approving mutates the MSW store so the flag appears live in the public feed. New reusable `App*`: `AppTextField`, `AppSwitch`, `AppDialog`, `AppTag`. **Punk tags** (`крадене на пари`/`кофти сделки`/`шуши-муши`, CLAUDE.md §1.0.1) added to the contract (`FlagPost.tags`, `ReviewDecision.tags`) + rendered on feed card & post detail. Both-mode QA done; 46 vitest green. Admin endpoints + `tags` documented in `API_SEAM.md`.

### ✅ Backend (Laravel, repo root) — merged, but thin public API
Schema migrated (pgvector, authorities, companies, tenders, tender_items, price_snapshots, ingest_records, **flags**, posts, subscribers); `ingest:run` pipeline; Sanctum auth; honeypot/blacklist; notifications; CI + GHCR release + auto-deploy.
⚠️ **Public API exposes only a generic CMS `Post`** (`GET /api/posts`, `/api/posts/{post}`). **No detector, and no flag / entity / price-series / search / graph endpoints. No real procurement data ingested yet.**

### 🟡 Scraper + embeddings (`apps/scraper`, Python) — on `feat/scraper` / `feat/embeddings`, **NOT merged**

## 🔴 THE one blocker: the FE↔BE seam
The frontend consumes a rich `FlagPost` contract (`apps/web/src/types/contract.ts`); the backend serves generic posts. **Real data is blocked until the backend ships the endpoints + shapes documented in [`apps/web/API_SEAM.md`](../apps/web/API_SEAM.md)** (flag-posts feed/detail, authorities, companies, price-series, graphs/serial-winner, regions/aggregate, search — plus `category` (CPV-derived) and `series_key`). Until then the frontend stays on MSW.

## ▶ What to do next (prioritized)
1. **[BACKEND — highest payoff] Make real data flow.** Implement the read endpoints per `API_SEAM.md` (Resources carrying `#[TypeScript]`), write **one detector** (overpricing or serial-winner → `Flag` rows), and ingest **one real source** (TED or data.egov first — see `.claude/rules/data-sources.md` + `SOURCES.md`). Merge `feat/scraper`.
2. **[FRONTEND] Flip to real data** once endpoints exist: `composer sync:api-types` → `apps/web/src/types/generated.d.ts`, point `types/api.ts` at it, reconcile vs `contract.ts`, set `VITE_ENABLE_MOCKS=false` + `VITE_API_URL`. Verify cookie/CORS/CSP `connect-src`.
3. ~~**[FRONTEND] Admin (Phase 4)**~~ — **DONE** (on MSW; see the Frontend section above). When the backend ships the admin endpoints from `API_SEAM.md`, the FE flips off mocks with the rest (#2). Note: admin auth + the review→publish reflection are real Sanctum/policy work server-side (the client guard is UX only).
4. **[FRONTEND] ▶ NEXT PHASE — start here.** Both-mode Playwright QA of the **viz** screens (admin screens already QA'd light+dark this session). Concretely, for a fresh agent:
   - Run `corepack pnpm -C apps/web dev`; open `/price/laptops`, `/network/comp-1`, `/map`.
   - For **each**, screenshot in **light AND dark** (header toggle) and confirm: chart line + outlier highlight render; the React-Flow serial-winner graph lays out + is themed; the choropleth colours by sphere/severity and the sector filter + click→region-feed drill-in work. Loading/empty/error states show (no blank/frozen screen).
   - Fix anything that breaks; keep all colours from theme/tokens (no raw hex). Then re-run the gates (§How to run) and tick `apps/web/ADMIN_PHASE4_CHECKLIST.md` §H.
5. **Stretch (Phase 5–6):** PWA install + RSS + Sentry; Playwright e2e + axe in CI; prod deploy/tunnel (Tier 3).

> ⚠️ **MSW state caveat (demo):** the admin store is in-memory — approvals/sources survive client-side navigation but reset on a **hard page reload** (and a reload logs the editor out). For the "watch it publish" live demo, navigate within the SPA; don't F5. Goes away once real backend endpoints are wired.
> 🔭 **Bigger picture:** the highest-payoff move overall is still #1 (backend real data) — a different lane. The frontend is integration-ready and waiting on the `API_SEAM.md` endpoints; #4 is the best independent frontend work until they land.

## How to run (Windows)
- Frontend: `corepack pnpm -C apps/web dev` · `… build` · `… typecheck` · `… lint` · `… exec vitest run`. Node 20; pnpm is corepack-only.
- If `/api/*` 404s in dev after edits: a stale **service worker** — DevTools → Application → unregister, hard-reload. (PWA SW is disabled in dev; MSW owns it.)

## Notes / gotchas for a new agent
- **Stay in your lane** (frontend · backend · scraping); seams are `API_SEAM.md` (FE↔BE) and the NDJSON contract (scraper→BE). Per-lane `CLAUDE.md` + `.claude/rules/`.
- The **Bash tool runs bash**, which strips PowerShell `$vars` inside `-Command "..."` → use a `.ps1` + `-File`, or arg forms without `$`.
- **`git push` is gated** (agent allowlist) → the human runs it (`! git push origin <branch>:main`).
- Map geo = **Eurostat GISCO NUTS3** (`apps/web/public/geo/bg-provinces.geojson`, attribution in `SOURCES.md`). ⚠️ `frontend.md` §10 says the map should be **Mapbox**; we deliberately shipped a **d3-geo choropleth** (offline, no token — demo-safe). Revisit if a token + live basemap is wanted.
- License decision (GPL vs MIT) still open (CLAUDE.md §2.5 vs `LICENSE`).

---

## Legend
- ⏱️ Speed: ⚡ hours · 🔨 ~half-day · 🏗️ a day+/risky
- 💪 How well it'll come out: 🟢 solid/prod-grade · 🟡 fine · 🔴 fragile (data/time risk)
- 🎯 Payoff (score + scope coverage): ⭐ low · ⭐⭐ good · ⭐⭐⭐ high

---

## Tier 0 — Foundation (do first; mostly fast; unblocks everything)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| Generate Laravel + Vite skeletons (`plan.txt` step 1) | ⚡ | 🟢 | ⭐⭐⭐ |
| `make build && up && migrate` — full stack running | ⚡ | 🟢 | ⭐⭐⭐ |
| Data model: `Tender · ContractingAuthority · Company · Flag` migrations | 🔨 | 🟢 | ⭐⭐⭐ |
| **pgvector enabled + vector columns** (unlocks 3 detectors + search) | ⚡ | 🟢 | ⭐⭐⭐ |
| `ingest:run` command — idempotent upsert from NDJSON | 🔨 | 🟢 | ⭐⭐⭐ |
| **TED scraper** → NDJSON (clean, structured, real BG data) | 🔨 | 🟢 | ⭐⭐⭐ |
| Commit a real `samples/*.ndjson` (demo can't die if upstream does) | ⚡ | 🟢 | ⭐⭐⭐ |

## Tier 1 — Product core (the demo lives or dies here)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| **Flag feed** page (the money shot) | 🔨 | 🟢 | ⭐⭐⭐ |
| Search + **entity pages** (company / authority history) | 🔨 | 🟢 | ⭐⭐⭐ |
| **Overpricing detector** (vector-clusters "same item, 5 spellings") | 🔨 | 🟡 | ⭐⭐⭐ |
| **Serial-winner detector** (vector + joins for shell clusters) | 🔨 | 🟡 | ⭐⭐⭐ |
| **Price-over-time graph** (MUI X charts) — big demo wow | ⚡🔨 | 🟢 | ⭐⭐⭐ |
| Punk theme/tokens + BG i18n scaffolding | ⚡ | 🟢 | ⭐⭐ |
| Source link on every flag (it's already in the contract) | ⚡ | 🟢 | ⭐⭐⭐ |

## Tier 2 — Scope expanders (take once Tier 1 is solid; cheap because the rails exist)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| 2nd source: **data.egov.bg** (more real coverage) | 🔨 | 🟡 | ⭐⭐ |
| **Doc-clone detector** (pure vector similarity — cheap once pgvector's in) | 🔨 | 🟢 | ⭐⭐ |
| **Semantic search** box (reuses the embeddings) | ⚡🔨 | 🟢 | ⭐⭐ |
| Cancelled-after-bids detector | 🔨 | 🟡 | ⭐⭐ |
| **PWA installable** ("mobile version" for ~free — plugin already in deps) | ⚡ | 🟢 | ⭐⭐ |
| Serial-winner **graph view** (high wow, some viz risk) | 🔨🏗️ | 🟡 | ⭐⭐ |

## Tier 3 — Prod-readiness (cheap wins — already scaffolded, just wire/flip on)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| Rate-limiting + CORS lockdown (mostly config) | ⚡ | 🟢 | ⭐⭐ |
| CI green on every push (already scaffolded) | ⚡ | 🟢 | ⭐⭐ |
| Public **HTTPS demo URL** via Cloudflare Tunnel | ⚡ | 🟢 | ⭐⭐ |
| Health checks + restart policies (already there) | ⚡ | 🟢 | ⭐ |
| Prod deploy: VM + `docker-compose.prod.yml` + Caddy TLS (real live URL) | 🔨 | 🟢 | ⭐⭐ |
| Error tracking (Sentry) | ⚡🔨 | 🟢 | ⭐ |

## Tier 4 — Stretch / likely traps (high effort, low score-per-hour → defer or skip)
| Item | ⏱️ | 💪 | 🎯 | Verdict |
|---|---|---|---|---|
| SEBRA late-payments source + detector | 🔨🏗️ | 🟡 | ⭐⭐ | only if Tier 1–2 done |
| Trade Register owners (shell links) | 🏗️ | 🔴 | ⭐⭐ | partly paywalled → **curate**, don't auto |
| Honeypot / tarpit / blacklist | 🏗️ | 🟡 | ⭐ | cool, scores ~0 → **skip for demo** |
| Prometheus / Grafana monitoring | 🏗️ | 🟡 | ⭐ | ~0 demo value → **skip** |
| Kubernetes | 🏗️ | 🟡 | ⭐ | Stage 2 (devops.md §8) → **skip** |
| Full auth / user accounts | 🔨 | 🟢 | ⭐ | public read-only tool barely needs it |
| ЦАИС ЕОП deep web-scrape | 🏗️ | 🔴 | ⭐⭐ | messy HTML, time-bomb → only if TED+egov fall short |

---

## The strategy in one line
**Tier 0 → Tier 1 must be done. Then interleave Tier 2 (scope) with the cheap Tier 3 (prod) wins. Touch Tier 4 only if you're ahead.** At every checkpoint, ask: *"is the demo path still solid?"* — if yes, add scope; if shaky, stop and harden.

## Prod-ready definition of done (the bar)
- [ ] Clean clone → `make up` → migrate → ingest sample → site works, from scratch.
- [ ] Runs on real ingested data (not mock), every flag sourced.
- [ ] CI green; rate-limited; HTTPS; health check passes.
- [ ] Cached real-data snapshot committed so a dead upstream can't kill the demo.
- [ ] Public URL reachable (tunnel or VM). README + LICENSE present.
