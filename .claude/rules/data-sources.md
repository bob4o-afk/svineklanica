# Data sources & sourcing discipline — Public Procurement Watchdog

> This is the spine of the project. **Every flag links to a primary source. No source → no flag.**
> ⚠️ The endpoints below are **candidates to verify on site** — confirm availability, format, and terms of use before relying on one for the demo. Don't hardcode an assumption I wrote here; check it.
> 🐍 **The fetching/normalizing is done in Python (`apps/scraper`), not Laravel.** This file = *which* sources + sourcing discipline; the *how* (output contract, idempotency, Cyrillic) lives in [`scraping.md`](scraping.md). Laravel only ingests the scraper's NDJSON output.

## 0. The iron rule (from CLAUDE.md)

- **Public data only.** Scraping public pages, public registries, OSINT of public info, open-data downloads. **No unauthorized access, no login-walled scraping, no rate-abuse.**
- Every ingested record stores its **`source_url`** and **`fetched_at`**. Every detector flag carries the `source_url`(s) it's derived from.
- Maintain **`SOURCES.md`** at the repo root: each source, what we pulled, when, format, and any terms/robots notes.

## 1. Candidate Bulgarian sources (verify each)

| Source | What it gives us | Why it matters | Format (verify) |
|---|---|---|---|
| **ЦАИС ЕОП** — `app.eop.bg` (Централизирана автоматизирана информационна система „Електронни обществени поръчки") | Modern central system: tenders, contracting authorities, bids, awards, contracts | Primary, current source of truth for most procurements | Web UI; check for export/open-data/API |
| **АОП / РОП** — Агенция по обществени поръчки (`aop.bg`, older `rop3-app1.aop.bg`) | Register of public procurement, historical notices | Historical depth, pre-ЕОП records | Web register; scrape |
| **TED** — `ted.europa.eu` (Tenders Electronic Daily) | EU-wide notices incl. above-threshold BG tenders | **Best structured/bulk source** — has open data + bulk XML/CSV | Open data, structured XML/CSV/API ✅ |
| **data.egov.bg** — Портал за отворени данни | Published datasets, possibly procurement + spending | Clean open data, no scraping needed | Datasets / API |
| **SEBRA** (Система за електронни бюджетни разплащания) via minfin.bg / data.egov.bg | Actual budget payments by spenders | **Powers the "delayed payments" detector** — contracted vs paid | Reports / open data |
| **Търговски регистър** — `portal.registryagency.bg` | Company EIK, address, owners, managers | **Powers "serial winner / shell company" clustering** — link companies by shared owner/address/EIK | Web; check open-data dumps |

**Demo strategy:** TED + data.egov.bg are the lowest-friction structured sources — get one of them ingesting **first** to guarantee real data in the demo. Treat ЕОП/РОП scraping as the deeper, higher-payoff target once the pipeline works.

## 2. Ingest discipline ⭐

- **Ingest-first, never live in the demo.** Scrape/import → normalize into our Postgres → run detectors → serve from our DB. Do not hit upstream during the pitch.
- **Idempotent upserts** on the source's natural key (tender registry number, TED notice ID, EIK). Re-running a scraper must not duplicate.
- Store **raw + normalized**: keep the original payload (or a snapshot/hash) alongside the parsed fields so we can prove provenance and re-parse without re-fetching.
- **Snapshots for price tracking:** the price-over-time graph needs point-in-time captures — store `(item, tender, price, captured_at, source_url)` so a price's history is real, not reconstructed.
- Be a **polite scraper:** respect robots, throttle, set a UA, cache aggressively. We're exposing bad actors, not becoming one.
- **Log skipped/failed records with reasons** (see backend rules §5) — the pitch should be able to state ingest honestly: "N ingested, M skipped because …".

## 3. Normalization notes (where the hard part lives)

- **Items/products** are free-text in Bulgarian → fuzzy-cluster for the price-discrepancy detector (same "laptop" written 5 ways). Start with simple normalization (lowercase, CPV code grouping, token match); upgrade only if time allows.
- **CPV codes** (Common Procurement Vocabulary) are the reliable category key — prefer them over free-text where present.
- **Companies** unify on **EIK** (БУЛСТАТ), not name (names vary, get renamed, and shells reuse addresses). Cross-link via Търговски регистър.
- **Money:** normalize currency (BGN/EUR) and with/without ДДС (VAT) before comparing prices — a 10-vs-100 gap is meaningless if one is per-unit and one is total, or one includes VAT.

## 4. Flag provenance schema (suggested)

Each `Flag` row: `type` (price_discrepancy | tailored_spec | serial_winner | cancelled | implausible_scope | delayed_payment | doc_clone), `severity`, `subject` (tender / authority / company), `explanation_bg` (plain-language why), `source_urls[]`, `evidence` (the numbers behind it), `detected_at`.

> If a reviewer clicks a flag and can't reach the original record, the flag is worthless — and on this theme, **unsourced = disinformation = disqualifying.**
