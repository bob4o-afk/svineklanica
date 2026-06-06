# SOURCES

Every dataset / static asset we pull from, per [`.claude/rules/data-sources.md`](.claude/rules/data-sources.md).
Per-flag primary sources live on each record (`source_url`); this file tracks **bulk / static** assets committed to the repo.

## Map geometry

| File | Source | Pulled | Format | License / terms |
|---|---|---|---|---|
| `apps/web/public/geo/bg-provinces.geojson` | **Eurostat GISCO** — NUTS 2021, level 3, 1:10M (`NUTS_RG_10M_2021_4326_LEVL_3`), filtered to `CNTR_CODE=BG`, stripped to `NUTS_ID` + geometry | 2026-06-06 | GeoJSON · 28 oblasti, keyed by `properties.NUTS_ID` (`BG311`…`BG425`) | ✅ Free to use **with attribution** (see below) |

**Attribution (required by GISCO):**
> © EuroGeographics for the administrative boundaries. Source: Eurostat GISCO.

Map keys on NUTS3 codes; Bulgarian display names live in `apps/web/src/lib/regions.ts`.

## Procurement data

_None committed yet._ Real procurement sources (TED, data.egov.bg, ЦАИС ЕОП, АОП/РОП, SEBRA, Търговски регистър) are ingested by the **Python scraper lane** (`apps/scraper`) → NDJSON → `php artisan ingest:run`; each record carries its own `source_url` + `fetched_at`. See [`.claude/rules/scraping.md`](.claude/rules/scraping.md).
