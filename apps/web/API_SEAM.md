# Frontend ⇄ Backend API seam (what the web app needs)

> **Status (2026-06-06): the frontend runs entirely on MSW mocks** against the shapes below.
> The merged backend currently exposes only a **generic CMS `Post`** (`GET /api/posts`,
> `/api/posts/{post}` → `{title, slug, excerpt, body, status, sourceUrls[]}`). That does **not**
> match the structured shapes the UI is built on. To put the app on **real data**, the backend
> needs to ship the read endpoints below returning these shapes (carry `#[TypeScript]` on the
> DTOs/Resources and regenerate `apps/web/src/types/generated.d.ts`; the frontend then flips
> `types/api.ts` from `contract.ts` → `generated.d.ts` and sets `VITE_ENABLE_MOCKS=false`).

The canonical TypeScript shapes are in [`src/types/contract.ts`](src/types/contract.ts). All ids are
external `public_id` (UUID); companies key on `eik`. Public reads are unauthenticated but must be
rate-limited (security.md). Responses are JSON.

## Public read endpoints the UI calls

| Method · path | Query params | Response (contract.ts) | Used by |
|---|---|---|---|
| `GET /api/flag-posts` | `type[]`, `category[]`, `severity[]`, `region`, `cpv`, `q`, `sort` (`newest`\|`severity`), `page`, `per_page` | `Paginated<FlagPost>` | feed, home teaser, entity-region drill-in |
| `GET /api/flag-posts/{public_id}` | — | `FlagPost` | post detail |
| `GET /api/authorities/{public_id}` | — | `AuthorityDetail` | authority page |
| `GET /api/companies/{eik}` | — | `CompanyDetail` (incl. shell-cluster `related[]`) | company page |
| `GET /api/price-series/{key}` | — | `PriceSeries` | price-over-time chart |
| `GET /api/graphs/serial-winner/{public_id}` | — | `SerialWinnerGraph` (empty `{nodes:[],edges:[]}` for none) | network graph |
| `GET /api/regions/aggregate` | `metric?` | `RegionAggregate[]` | corruption map |
| `GET /api/search` | `q` | `SearchResults` | global search |

## Admin endpoints (Sanctum SPA cookie) — frontend Phase 4 SHIPPED (on MSW)

The admin area (login → review queue → approve/reject + sources CRUD) is **built and running on
MSW**. Auth is the **Sanctum SPA-cookie** flow: `GET /sanctum/csrf-cookie` (app root, **not** under
`/api`) primes the `XSRF-TOKEN` cookie, then the calls below run with the session cookie + the
`X-XSRF-TOKEN` header (the `http` wrapper already sends both; no token in JS). **Every admin route
must enforce the session + an editor policy server-side** (security.md §1) — the client guard is UX
only. Reject anonymous callers with **401**.

| Method · path | Body / params | Response | Used by |
|---|---|---|---|
| `GET /sanctum/csrf-cookie` | — | `204` (sets `XSRF-TOKEN`) | login |
| `POST /api/admin/login` | `{email, password}` | `AdminUser` (or `422` on bad creds) | login |
| `POST /api/admin/logout` | — | `204` | logout |
| `GET /api/admin/me` | — | `AdminUser` (or `401` when anonymous) | AuthProvider/session |
| `GET /api/admin/flag-posts` | `status` (`pending` default), `page`, `per_page` | `Paginated<FlagPost>` | review queue |
| `GET /api/admin/flag-posts/{public_id}` | — | `FlagPost` (**any** status, unlike the public detail) | review panel |
| `POST /api/admin/flag-posts/{public_id}/approve` | `ReviewDecision` (`{title?, explanation_bg?, note?, tags?}`) | `FlagPost` | publish |
| `POST /api/admin/flag-posts/{public_id}/reject` | `{note?}` | `FlagPost` | reject |
| `GET /api/admin/sources` | — | `Source[]` | sources registry |
| `POST /api/admin/sources` | `Omit<Source,'public_id'\|'last_ingested_at'>` | `Source` (`201`) | add source |
| `PATCH /api/admin/sources/{public_id}` | `Partial<…>` | `Source` | edit / toggle `enabled` |
| `DELETE /api/admin/sources/{public_id}` | — | `204` | delete source |

> Approving a flag flips `status` → `approved` (sets `published_at`, applies the editor's edits +
> `tags`); it then appears in the **public** `GET /api/flag-posts` feed and drops out of the queue.

## Field notes the backend must honour

- **`FlagPost.category`** — `ProcurementSector` (`health|education|roads|construction|it|utilities|supplies|other`). **Derive from CPV** at ingest/detection (same buckets as `apps/web/src/lib/sectors.ts::sectorFromCpv`). Powers the feed „Сектор" facet + map dimension.
- **`FlagPost.tags`** — `PunkTag[]` (`theft|dodgy_deal|shushi_mushi` — the „крадене на пари / кофти сделки / шуши-муши" editorial badges, CLAUDE.md §1.0.1). **Admin-assigned on approval**, NOT computed; persist what the approve call sends. Labels are i18n (`flags:tag.*`), so the API carries the **enum keys**, not Bulgarian text.
- **`FlagPost.series_key`** — present on `price_discrepancy` flags; the key for `GET /api/price-series/{key}` (same product/CPV cluster the detector grouped on).
- **`region_code`** — NUTS3 oblast code (`BG411` София-град, `BG421` Пловдив, …) on `AuthorityRef.region_code` and `RegionAggregate.region_code`, matching `public/geo/bg-provinces.geojson` `properties.NUTS_ID` and `apps/web/src/lib/regions.ts`.
- **`sources[]` ≥ 1** on every `FlagPost` — no source → no flag (CLAUDE.md §0). `PricePoint.source` likewise.
- Money is `{amount, currency: 'BGN'|'EUR', vat_included}` — normalize VAT/currency **before** comparing (data-sources.md §3).

## Not the backend's job
`GET /geo/bg-provinces.geojson` is a committed **frontend static asset** (see `SOURCES.md`), fetched same-origin — not an API endpoint.
