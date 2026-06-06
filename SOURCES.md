# SOURCES — Свинекланица Watchdog

> **The iron rule:** every ingested record stores its `source_url` + `fetched_at`; every flag links to the primary source. **No source → no flag** (`.claude/rules/data-sources.md` §0).
> This file is the canonical list of **where the data comes from**, mapped to the **Sphere → Category** model (`CLAUDE.md` §1.0). The *how* (NDJSON contract, idempotency, Cyrillic, politeness) lives in `.claude/rules/scraping.md`.
>
> **Status legend:** ✅ verified reachable · 🟡 reachable, format/terms to confirm on-site · 🔴 not yet checked.
> **Discipline:** public data only, polite scraping (robots + throttle), no login-walled or rate-abused sources. Last reviewed: **2026-06-06**.

---

## 1. Cross-cutting — public procurement (category `обществена поръчка`)

These cover *all* spheres; filter by contracting authority to attribute to judiciary / healthcare / police.

| Source | URL | What we pull | Format | Natural key | Status |
|---|---|---|---|---|---|
| **TED — Tenders Electronic Daily** (EU) | API: `https://docs.ted.europa.eu/api/latest/` · Open Data (SPARQL): `https://data.ted.europa.eu/` · CSV subset: `https://data.europa.eu/data/datasets/ted-csv` · bulk XML: TED portal | Above-threshold BG notices: authority, value, winner, awards | **Structured XML/CSV/JSON, no auth** ✅ best bulk source | TED notice id | ✅ |
| **ЦАИС ЕОП** (central e-procurement) | `https://app.eop.bg/today` (UI) | Current tenders, authorities, bids, awards, contracts — the source of truth post-2020 | Web UI; export per-record; **open-data slices via data.egov.bg** | tender registry № | 🟡 (UI; check machine export) |
| **АОП open data** (Агенция по обществени поръчки) | `https://www2.aop.bg/aop-publikuva-otvoreni-danni/` · register `https://www2.aop.bg/` | Annual register of public procurements (incl. historical / pre-ЕОП) | Open data files (per year) | registry № | 🟡 |
| **data.egov.bg** (Open Data Portal) | `https://data.egov.bg` (AOP datasets: "Регистър на обществените поръчки") | Cleaned procurement datasets, framework agreements | Datasets / CKAN-style API | dataset row id | ✅ |

**Demo strategy:** get **TED** ingesting **first** (cleanest bulk, no auth), then layer ЕОП/АОП for BG depth.

---

## 2. Cross-cutting — payments (category `нерегламентирани плащания`)

| Source | URL | What we pull | Format | Status |
|---|---|---|---|---|
| **СЕБРА — ежедневни бюджетни плащания** (Министерство на финансите) | `https://www.minfin.bg/bg/transparency` (daily, by date) | Every budget payment **≥ 5000 лв** (excl. salaries/insurance): payer, payee, amount, date, payment-type code | Daily reports + open data | ✅ |
| **СЕБРА — тримесечни плащания (open data)** | `https://data.egov.bg` (quarterly individual payments ≥5000 лв) | Same, quarterly machine-readable slice — powers **contracted-vs-paid** (delayed-payment detector) | Open data | ✅ |
| **СЕБРА кодове за вид плащане** (reference) | `https://e-gov.bg/.../sebra-info` | Lookup of payment-type codes (10–90) to label payments | Reference table | 🟡 |

> Match a СЕБРА payment to a procurement contract (payer = authority, payee EIK = winner) → the contracted-vs-actually-paid timeline. Off-contract or chronically-late payments = the `нерегламентирани плащания` flag.

---

## 3. Company / entity resolution (powers serial-winner & shell-company clustering)

| Source | URL | What we pull | Format | Natural key | Status |
|---|---|---|---|---|---|
| **Търговски регистър** (Агенция по вписванията) | Portal `https://portal.registryagency.bg/` · **Register API** (JSON, daily) `https://www.registryagency.bg/bg/registri/targovski-registar/predostavyane-na-dostap-do-bazata-danni-na-targovskiya-registar/` · open-data dump on `data.egov.bg` | EIK/БУЛСТАТ, name, address, managers, owners, capital, status | JSON API / open-data dump (history, PII removed) | **EIK** | 🟡 (API access terms to confirm) |

> Unify companies on **EIK**, never name. Shared address / owner / phone across EIKs → shell-cluster signal.

---

## 4. Sphere-specific contracting authorities (demo focus)

Each ministry/body runs a **"профил на купувача"** (buyer profile) — primary-source tenders we can attribute to the sphere directly. Prefer pulling these via ЦАИС ЕОП/TED keyed by the authority; the profiles below are the human-readable fallback + provenance link.

### 🏛️ Съдебна система (judiciary)
| Source | URL | What | Status |
|---|---|---|---|
| **ВСС — профил на купувача** (Висш съдебен съвет) | `https://profile-op.vss.justice.bg/` · info `https://vss.justice.bg/` | Procurements of the judiciary's governing body | 🟡 |

### 🏥 Здравеопазване (healthcare)
| Source | URL | What | Status |
|---|---|---|---|
| **НЗОК** (Национална здравноосигурителна каса) | `https://www.nhif.bg/` — contracts w/ hospitals/traders, paid activity-code prices (Excel), РЗОК contract lists | Payments to hospitals + contracted-activity prices → overpricing & payment detectors | 🟡 |
| **Министерство на здравеопазването — профил на купувача** | `https://www.mh.government.bg/` (verify path) | Ministry-level medical procurement (equipment, drugs) | 🔴 |

### 👮 Полиция (police)
| Source | URL | What | Status |
|---|---|---|---|
| **МВР — Дирекция „Обществени поръчки"** | `https://www.mvr.bg/dop` · buyer profiles under `mvr.bg/.../профил-на-купувача` | Ministry of Interior procurements (vehicles, gear, IT) | 🟡 |

---

## 5. Backlog sphere — образование (category `конкурси за работа`, `CLAUDE.md` §1.4)

Rigged hiring: short deadline + чл. 67 (КТ) + ultra-specific qualification. **Public РУО archives only.**

| Source | URL | What | Status |
|---|---|---|---|
| **РУО — свободни работни места / конкурси** (28 regional offices) | e.g. Бургас `https://ruoburgas.bg/zaemane-na-dlyjnost.html` · Пловдив `https://www.ruoplovdiv.bg/jobs` · Варна `http://ruo-varna.bg/` · (one site per region) | Job adverts: position, deadline, required qualification, legal basis (чл. 67 / Наредба №15) | 🟡 |
| **МОН — свободни работни места** | `https://www.mon.bg/` | Ministry-aggregated education vacancies | 🔴 |

---

## 6. Reference / aggregators (NOT primary sources for flags)

For hero-case research and sanity-checks only — a flag must still cite a §1–§5 primary record, never one of these.

| Source | URL | Use |
|---|---|---|
| **Данни за добро / Data for Good** — SEBRA visualization | `https://data-for-good.bg/posts/2022-01-20-sebra-visualization-bg/` | Prior art on SEBRA payment viz |
| **Bivol / BIRD** | `https://bivol.bg` | Investigative leads on known cases |

---

## 7. Static map geometry (frontend asset, not a flag source)

| File | Source | Pulled | Format | License / terms |
|---|---|---|---|---|
| `apps/web/public/geo/bg-provinces.geojson` | **Eurostat GISCO** — NUTS 2021, level 3, 1:10M (`NUTS_RG_10M_2021_4326_LEVL_3`), filtered to `CNTR_CODE=BG`, stripped to `NUTS_ID` + geometry | 2026-06-06 | GeoJSON · 28 oblasti, keyed by `properties.NUTS_ID` (`BG311`…`BG425`) | ✅ Free with attribution |

> Attribution (required): **© EuroGeographics for the administrative boundaries. Source: Eurostat GISCO.** The map keys on NUTS3 codes; Bulgarian display names live in `apps/web/src/lib/regions.ts`.

---

## Per-record provenance checklist (every ingested row)
- [ ] `source` (which §1–§5 source) · `natural_key` (TED id / registry № / EIK)
- [ ] `source_url` — a page/document a human can open · `fetched_at` (ISO-8601 UTC)
- [ ] raw snapshot kept (re-parse without re-fetch) · `sphere` + `category` tagged where inferable
- [ ] location (region/municipality, lat/lng) for the map where available
