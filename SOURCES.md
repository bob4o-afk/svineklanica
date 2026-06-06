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

### 🏥 Здравеопазване (healthcare flow)

| id | Source | What we pull | Format | natural_key | Access / notes |
|----|--------|--------------|--------|-------------|----------------|
| **ncpr** | NCPR (НСЦРЛП) | Drug ceiling prices (benchmark) | CSV | product+holder hash | Open data (ncpr.bg) ✅ |
| **nhif** | NHIF (НЗОК) | NHIF/RZOK tenders | HTML | procedure id / hash | Public profile ✅ |
| **mz** | МЗ | Ministry of Health tenders | HTML | procedure id / hash | Public profile ✅ |
| **mz_jobs** | МЗ Конкурси | Hospital director / board competitions | HTML | job id / hash | mh.government.bg/konkursi ✅ |
| **mz_assets** | МЗ Активи | Sale of hospital equipment / vehicles | HTML | auction id / hash | Configurable; may be sparse |

### 🏛️ Съдебна система (judiciary flow)

| id | Source | What we pull | Format | natural_key | Access / notes |
|----|--------|--------------|--------|-------------|----------------|
| **vss** | ВСС | Judiciary governing body tenders | HTML | procedure id / hash | Public profile ✅ |
| **prb** | Прокуратура на РБ | Prosecutor's Office tenders | HTML | procedure id / hash | Public profile ✅ |
| **vss_jobs** | ВСС Конкурси | Magistrate / admin competitions | HTML | job id / hash | vss.justice.bg ✅ |
| **ivss_declarations** | ИВСС | Magistrate property declarations (ZSV art. 19a) | HTML table | row hash | inspectoratvss.bg ✅ |
| **mjs_assets** | МП Активи | Sale of court buildings / vehicles | HTML | auction id / hash | mjs.bg ✅ |

### 👮 Полиция (police flow)

| id | Source | What we pull | Format | natural_key | Access / notes |
|----|--------|--------------|--------|-------------|----------------|
| **mvr** | МВР | Police tenders (uniforms, gear) | HTML | procedure id / hash | Public profile ✅ |
| **mvr_donations** | МВР Дарения | Register of donations to MVR | HTML | row hash | Public profile ✅ |
| **mvr_jobs** | МВР Конкурси | Job competitions in MVR | HTML | job id / hash | Public profile ✅ |
| **mvr_assets** | МВР Активи | Sale of state assets/real estate | HTML | auction id / hash | Public profile ✅ |

### 🏛️ Cross-cutting (all spheres)

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

## AI corruption verdicts — backend handoff

The AI layer (`apps/ai`, LangChain + Gemini) reads the normalized corpus and
writes a **verdict sidecar**, keyed by `natural_key` (the ingest contract is
unchanged). Produce it with `uv run analyze --source <x>` (or one record with
`uv run analyze-one --source <x> --natural-key <key>`, which the backend control
panel calls on demand):

```
storage/ingest/verdicts/<source>.ndjson
{"source","natural_key","source_url","analyzed_at","model",
 "corruption_score":0-100,"level","hard_tripped",
 "sphere","category","flow_key",
 "signals":[{key,family,code,risk,weight,contribution,value,source_field,rationale_bg}],
 "flags":[{type,severity,subject,source_urls[],explanation_bg,evidence}],
 "agent_outputs":{...},"headline_bg","explanation_bg"}
```

- **`flags[]` matches the backend Flag schema 1:1** (this file's §4 / `data-sources.md`):
  `type, severity, subject, source_urls[], explanation_bg, evidence`. Ingest them
  as `Flag` rows; store `corruption_score` + `level` as extra columns on the tender.
- **Score = deterministic math** (auditable: every signal's weight + contribution
  is in `signals[]`). Hard-trip rules force 99/100 on strong, sourced combinations;
  otherwise a per-family noisy-OR is weighted and passed through a logistic.
- **Levels:** `Корупция` (≥85 / hard-trip) · `Висок риск` (65–85) · `Съмнително`
  (40–65) · `Нисък риск` (20–40) · `Нормално` (<20).
- **Criteria catalog:** ~60 parameters grounded in Open Contracting R001–R073,
  OECD bid-rigging, IACRC/DoD/GSA fraud schemes, World Bank/opentender CRI, real
  КЗК cartel cases, and Benford's law — see `apps/ai/src/analyzer/features/`.
- **No source → no flag** (the iron rule) is enforced in `scoring.py`.
- A committed demo slice lives in `storage/ingest/samples/verdicts/<source>.ndjson`.

### Healthcare AI flows (`здравеопазване`)

Run: `uv run analyze --sphere healthcare` (shared NCPR drug index + all healthcare sources).

| flow_key | category | Primary sources | Focus |
|----------|----------|-----------------|-------|
| `drugs` | лекарства | ncpr, pharma CPV | NCPR ceiling, INN steering, overpricing |
| `procurement` | обществена поръчка | nhif, mz | Spec rigging, collusion, lifecycle |
| `jobs` | конкурси за работа | mz_jobs | Rigged competitions, kinship/conflict |
| `assets` | продажба на активи | mz_assets | Undervalued sales, restrictive auctions |

Routing: source id → payload category → CPV 33xx → LLM `category_router` (Gemini 3.1 Flash Lite).

### Judiciary AI flows (`съдебна система`)

Run: `uv run analyze --sphere judiciary`.

| flow_key | category | Primary sources | Focus |
|----------|----------|-----------------|-------|
| `procurement` | обществена поръчка | vss, prb | Spec rigging, collusion, lifecycle |
| `jobs` | конкурси за работа | vss_jobs | Magistrate competitions, kinship/conflict |
| `declarations` | нерегламентирани плащания | ivss_declarations | Unexplained wealth, late filings (ZSV 19a) |
| `assets` | продажба на активи | mjs_assets | Undervalued court property sales |

Routing: source id → payload category → heuristics → LLM `judiciary_category_router`.

### Police AI flows (`полиция`)

Run: `uv run analyze --sphere police`.

| flow_key | category | Primary sources | Focus |
|----------|----------|-----------------|-------|
| `procurement` | обществена поръчка | mvr | Spec rigging, collusion, lifecycle |
| `jobs` | конкурси за работа | mvr_jobs | Rigged competitions, kinship/conflict |
| `assets` | продажба на активи | mvr_assets | Undervalued police property sales |
| `donations` | дарения за МВР | mvr_donations | Donor influence, pay-to-play, repeat donors |

Routing: source id → payload category → heuristics → LLM `police_category_router`.

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
