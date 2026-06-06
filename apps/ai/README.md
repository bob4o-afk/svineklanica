# apps/ai — AI corruption-risk analyzer

LangChain + Google Gemini multi-agent system that turns the scraper's normalized
procurement records into an **auditable 0–100 corruption score** with a level
(`Корупция` · `Висок риск` · `Съмнително` · `Нисък риск` · `Нормално`) and a list
of sourced **flags** the Laravel backend ingests.

> Lane: this is the **Python AI layer** (like `apps/scraper`). It reads files the
> scraper produced and writes files the backend ingests — it never touches the
> database. Every flag carries the record's `source_url` (no source → no flag).

## How it works

```
normalized NDJSON corpus
   │
   ├─ ContextBuilder        cross-record aggregates (serial winner, buyer
   │                        dependence, CPV price peers, embedding neighbours)
   ├─ feature extractors    deterministic math, ~60 red-flag parameters
   │                        (OCP R001–R073, OECD bid-rigging, КЗК, Benford…)
   ├─ LLM agents (parallel) Gemini, structured JSON output, Markdown prompts:
   │                        spec-rigging · scope · lifecycle · entity · collusion
   ├─ scorer                hard-trip rules (→99/100) + per-family noisy-OR,
   │                        weighted, logistic → 0–100 + level
   └─ aggregator            citizen-facing explanation_bg (never sets the score)
            │
            └─► storage/ingest/verdicts/<source>.ndjson   (+ committed sample)
```

The **score is computed by deterministic math** — the LLM agents only supply
per-signal confidences (which feed the math) and the human-readable narrative.
This keeps the number defensible and reproducible.

## Setup

```bash
cd apps/ai
uv sync
cp .env.example .env   # then put your real GOOGLE_API_KEY in .env (gitignored)
```

`.env` is gitignored (root `.gitignore`: `.env*`). **Never commit a real key.**

## Healthcare sphere flows (per category)

For `здравеопазване`, records are routed to one of four category flows:

| Flow | Category | Sources | Agents |
|------|----------|---------|--------|
| `drugs` | лекарства | ncpr, pharma CPV tenders | drug_overpricing, inn_steering, scope, entity |
| `procurement` | обществена поръчка | nhif, mz, general tenders | spec_rigging, scope, lifecycle, entity, collusion |
| `jobs` | конкурси за работа | mz_jobs | rigged_competition, conflict_kinship, entity |
| `assets` | продажба на активи | mz_assets | undervalued_sale, scope, entity |

Routing: source id map → payload category → CPV 33xx → LLM category_router fallback.

```bash
# Analyze all healthcare sources with shared NCPR drug index
uv run analyze --sphere healthcare

# Single record with healthcare context
uv run analyze-one --sphere healthcare --source nhif --natural-key <key>
```

Every verdict includes `sphere`, `category`, `flow_key`, `corruption_score`, `level`, and `flags[]`.

## Judiciary sphere flows (per category)

For `съдебна система`, records are routed to one of four category flows:

| Flow | Category | Sources | Agents |
|------|----------|---------|--------|
| `procurement` | обществена поръчка | vss, prb | spec_rigging, scope, lifecycle, entity, collusion |
| `jobs` | конкурси за работа | vss_jobs | magistrate_competition, conflict_kinship, entity |
| `declarations` | нерегламентирани плащания | ivss_declarations | unexplained_wealth, conflict_kinship |
| `assets` | продажба на активи | mjs_assets | undervalued_sale, scope, entity |

Routing: source id map → payload category → heuristics → LLM `judiciary_category_router` fallback.

```bash
uv run analyze --sphere judiciary
uv run analyze-one --sphere judiciary --source ivss_declarations --natural-key <key>
```

## Police sphere flows (per category)

For `полиция`, records are routed to one of four category flows:

| Flow | Category | Sources | Agents |
|------|----------|---------|--------|
| `procurement` | обществена поръчка | mvr | spec_rigging, scope, lifecycle, entity, collusion |
| `jobs` | конкурси за работа | mvr_jobs | rigged_competition, conflict_kinship, entity |
| `assets` | продажба на активи | mvr_assets | undervalued_sale, scope, entity |
| `donations` | дарения за МВР | mvr_donations | donation_influence, entity |

Routing: source id map → payload category → heuristics → LLM `police_category_router` fallback.

```bash
uv run analyze --sphere police
uv run analyze-one --sphere police --source mvr_donations --natural-key <key>
```

## Government sphere flows (per category)

For `правителство`, records are routed to one of five category flows:

| Flow | Category | Sources | Agents |
|------|----------|---------|--------|
| `procurement` | обществена поръчка | gov_tenders | spec_rigging, scope, lifecycle, entity, collusion |
| `jobs` | конкурси за работа | gov_jobs | rigged_competition, conflict_kinship, entity |
| `audits` | одити | gov_audits | audit_findings |
| `gov_declarations` | имуществени декларации | gov_declarations | gov_official_wealth, conflict_kinship |
| `concessions` | концесии | gov_concessions | concession_abuse, scope, lifecycle |

Routing: source id map → payload category → heuristics → LLM `government_category_router` fallback.

```bash
uv run analyze --sphere government
uv run analyze-one --sphere government --source gov_declarations --natural-key <key>
```

## Roads sphere flows (per category)

For `пътно строителство`, records are routed to one of three category flows:

| Flow | Category | Sources | Agents |
|------|----------|---------|--------|
| `procurement` | обществена поръчка | api_tenders, mrrb_tenders, avtomagistrali_tenders | spec_rigging, scope, lifecycle, entity, collusion |
| `jobs` | конкурси за работа | api_jobs | rigged_competition, conflict_kinship, entity |
| `projects` | инфраструктурни проекти | api_projects | project_abuse, scope, lifecycle, entity |

Cross-cutting sources (`ted`, `caiseop`, …) with CPV `45233*` and `sphere=пътно строителство` route to `procurement`; project-shaped records route to `projects`.

Routing: source id map → payload category → heuristics → LLM `roads_category_router` fallback.

```bash
uv run analyze --sphere roads
uv run analyze-one --sphere roads --source api_projects --natural-key <key>
echo '<IngestRecord json>' | uv run analyze-one --stdin --sphere roads
```

## Run

```bash
# Score every record of a source -> verdict sidecar + sample slice
uv run analyze --source ted

# Deterministic-only (no Gemini calls / no tokens)
uv run analyze --source ted --no-llm

# Score ONE record (what the backend control panel calls)
uv run analyze-one --source ted --natural-key 387269-2026
echo '<IngestRecord json>' | uv run analyze-one --stdin

uv run analyze --list      # known sources
```

Output: `storage/ingest/verdicts/<source>.ndjson` (idempotent on `natural_key`)
plus a small committed slice in `storage/ingest/samples/verdicts/<source>.ndjson`.

## Configuration (`.env`)

| Key | Default | Meaning |
|---|---|---|
| `GOOGLE_API_KEY` | — | Gemini key (via `langchain-google-genai`). Empty → deterministic-only. |
| `GEMINI_MODEL` | `gemini-3.1-flash-lite` | Model id. If rejected, try `gemini-3.1-flash`. |
| `GEMINI_THINKING_LEVEL` | `low` | Gemini 3 reasoning depth (`low`/`medium`/`high`). |
| `GEMINI_TEMPERATURE` | `0` | Lower = more deterministic / auditable. |
| `ANALYZE_LIMIT` | — | Max records per run. |
| `AGENTS_CAP` | `8` | Max **concurrent** LLM agent calls in flight (the async ceiling). |
| `AGENTS_EVAL_CAP` | `100` | Max **total** LLM evaluations per run; once spent, remaining records degrade to deterministic-only. `0` = unlimited. |
| `ANALYZER_WEIGHTS_PATH` | — | JSON overriding per-family weights. |
| `EMBED_BACKEND` / `EMBED_MODEL` | scraper defaults | Used for re-tender / doc-clone similarity. |

### Concurrency & cost caps

Records are scored **concurrently** through a bounded worker pool, and every Gemini
call passes through two process-wide governors (`llm.py`):

- **`AGENTS_CAP`** — a semaphore: never more than N agent calls run at once (protects
  rate limit / quota). The record worker pool is sized to it too.
- **`AGENTS_EVAL_CAP`** — a total budget: after N evaluations the analyzer stops
  calling Gemini for the rest of the run and falls back to the deterministic math
  (honestly logged: *"evaluation budget exhausted"*). Bounds the cost of one run.

Both also accept being set in `.env` / `.env.prod`; the `AGENDS_CAP` misspelling is
tolerated as a fallback for `AGENTS_CAP`.

## Testing

```bash
uv run pytest            # offline; stub LLM, zero API calls / tokens
uv run pytest --run-llm  # opt-in: one real Gemini call (needs GOOGLE_API_KEY)
uv run ruff check .
```

## Docker

```bash
# Local (on-demand): the `ai` service is on the `ai` profile.
docker compose run --rm ai uv run analyze --source ted

# Prod VM: the `ai` service is on the `tools` profile, image pulled from GHCR
# (svineklanitsa-ai, built by release.yml). It shares the `ingest_data` volume
# with the app, so its verdicts are visible to `php artisan ingest:run`.
docker compose --env-file .env.prod -f docker-compose.prod.yml --profile tools \
  run --rm ai uv run analyze --source ted
```

Build context is `./apps` so the editable `procurement-scraper` dependency resolves.

**On every release tag**, the deploy job runs scrape → analyze → ingest → detect
automatically for the sources in the `SCRAPE_SOURCES` repo variable (default `ted`),
with the caps above applied from `.env.prod`. See `DEPLOY.md`.
