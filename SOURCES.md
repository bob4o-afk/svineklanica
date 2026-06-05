# SOURCES.md — data provenance

> Every flag links to a primary source. **No source → no flag.** This file lists
> every source the scraper (`apps/scraper`) pulls from: what we take, the format,
> the natural key, and robots/terms notes. Required by `.claude/rules/scraping.md` §5.
>
> Discipline: public data only · polite (robots, throttle, real UA) · ingest-first
> (never hit upstream live in the demo) · raw + normalized kept for provenance.

## How it flows

```
apps/scraper (Python)  ──NDJSON──▶  storage/ingest/normalized/<source>.ndjson
                                    storage/ingest/raw/<source>/<hash>.<ext>   (provenance)
                                    storage/ingest/samples/<source>.ndjson     (committed demo slice)
                                          │
                                          ▼
                       php artisan ingest:run --source=<x>   (Laravel, idempotent upsert on natural_key)
```

Run: `uv run scrape --source <x>` (or `--all`). List: `uv run scrape --list`.

## Sources

| id | Source | What we pull | Format | natural_key | Access / notes |
|----|--------|--------------|--------|-------------|----------------|
| **ted** | TED — Tenders Electronic Daily (`ted.europa.eu`) | EU notices incl. above-threshold BG tenders: title, buyer, value, CPV, dates | JSON (Search API v3, **POST** `api.ted.europa.eu/v3/notices/search`) | `publication-number` (e.g. `387269-2026`) | Open data. Default query `buyer-country=BGR`. ✅ live-verified, real BG sample committed. |
| **egov** | data.egov.bg — National Open Data Portal | Procurement datasets (rows of whichever resource is configured) | JSON (custom API, **POST** `/api/getResourceData` with `resource_uri`) | resource_uri + row id/hash | Open re-use (PSI Directive). Set `EGOV_RESOURCES` to the resource_uri(s) on site. |
| **caiseop** | ЦАИС ЕОП contracts (via data.egov.bg) | Awarded contracts: authority, winner (name+EIK), value, CPV, sign date | CSV (`;`-delimited, UTF-8/cp1251) | contract/registry number, else row hash | Public record. Set `CAISEOP_CSV_URLS` to the bulk CSV URL(s). Powers serial-winner + overpricing. |
| **aop** | АОП / РОП — Register of public procurement (`aop.bg`) | Historical (pre-ЕОП) notices listing | HTML table | notice/decision number, else row hash | Public register; legacy cp1251. Set `AOP_PAGES`. |
| **sebra** | SEBRA budget payments (via `minfin.bg` / open data) | Actual payments by spenders: spender, recipient, amount, date | CSV | row hash | Public. Powers the delayed-payments detector. Set `SEBRA_CSV_URLS`. |
| **eop** | ЦАИС ЕОП search UI (`app.eop.bg`) | Modern central system search results | HTML (JS-rendered → Playwright) | doc id from URL, else hash | Public; needs `browser` extra. WAF/JS-heavy. Set `EOP_PAGES`. |
| **isun** | ИСУН 2020 EU-funds (`2020.eufunds.bg`) | EU-funded beneficiaries, grant amounts | HTML (WAF → Playwright) | row hash | Public transparency data; WAF 403s non-browser. Needs `browser` extra. Set `ISUN_PAGES`. |

## Embeddings (semantic search) — backend handoff

Vectors are produced **in Python** (`uv run embed --source <x>`) and written as a
sidecar, keyed by `natural_key` so the normalized ingest contract is unchanged:

```
storage/ingest/embeddings/<source>.ndjson
{"source","natural_key","source_url","model","dim","text","embedding":[...float...]}
```

- **What we embed:** a composed searchable document per record (subject/title +
  authority/winner names + CPV) — see `apps/scraper/src/scraper/searchable.py`.
- **Model (default):** `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`
  via fastembed (ONNX, CPU). **dim = 384.** Multilingual incl. Bulgarian.
  Configurable via `EMBED_MODEL` / `EMBED_BACKEND` (fastembed | sentence-transformers).
- **Distance:** cosine (vectors are L2-normalized).
- **Backend wiring (pgvector):** add a `vector(384)` column, load by joining the
  sidecar on `natural_key`, index with HNSW (`vector_cosine_ops`), and embed the
  search query with the **same** model. The same vectors also feed the
  overpricing / doc-clone / serial-winner detectors (`backend.md` §12).
- **Demo / proof:** `uv run search --source ted "компютърни монитори"` ranks the
  matching notice first — pure Python, no backend needed.

## Conventions

- **Cyrillic** stays Bulgarian, emitted UTF-8 (`ensure_ascii=false`); bytes are
  decoded chardet → cp1251/utf-8 (`.claude/rules/scraping.md` §3).
- **Companies** unify on **EIK** (БУЛСТАT, checksum-validated), not name.
- **Money** is normalized to `{amount, currency}`; VAT/per-unit caveats noted downstream.
- **Idempotent** on `natural_key`; re-running replaces, never appends.

## Skipped for now (paywall / ToS-grey / heavy)

- **Търговски регистър** (`portal.registryagency.bg`) — no free bulk; systematic
  scraping is ToS-grey + CAPTCHA. Use EIK as the join key; curate owners if needed.
- **Asset declarations** (`bulnao.government.bg`) — PDF/OCR + GDPR sensitivity.
- **Court acts / State Gazette / lex.bg** — PDF-only / restrictive ToS.
