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

Admin (Sanctum cookie) endpoints land in frontend Phase 4 — not needed yet.

## Field notes the backend must honour

- **`FlagPost.category`** — `ProcurementSector` (`health|education|roads|construction|it|utilities|supplies|other`). **Derive from CPV** at ingest/detection (same buckets as `apps/web/src/lib/sectors.ts::sectorFromCpv`). Powers the feed „Сектор" facet + map dimension.
- **`FlagPost.series_key`** — present on `price_discrepancy` flags; the key for `GET /api/price-series/{key}` (same product/CPV cluster the detector grouped on).
- **`region_code`** — NUTS3 oblast code (`BG411` София-град, `BG421` Пловдив, …) on `AuthorityRef.region_code` and `RegionAggregate.region_code`, matching `public/geo/bg-provinces.geojson` `properties.NUTS_ID` and `apps/web/src/lib/regions.ts`.
- **`sources[]` ≥ 1** on every `FlagPost` — no source → no flag (CLAUDE.md §0). `PricePoint.source` likewise.
- Money is `{amount, currency: 'BGN'|'EUR', vat_included}` — normalize VAT/currency **before** comparing (data-sources.md §3).

## Not the backend's job
`GET /geo/bg-provinces.geojson` is a committed **frontend static asset** (see `SOURCES.md`), fetched same-origin — not an API endpoint.
