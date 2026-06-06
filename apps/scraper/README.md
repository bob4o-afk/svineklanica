# apps/scraper — Python scraping layer

The **scrapers are Python**; the **backend is Laravel**. This layer fetches public
Bulgarian procurement data, normalizes it (Cyrillic-safe), and writes files.
**Laravel never scrapes — it ingests these files.** Full rules:
[`/.claude/rules/scraping.md`](../../.claude/rules/scraping.md). Source catalog:
[`/SOURCES.md`](../../SOURCES.md).

## The handoff contract (the third seam)
Source of truth: [`src/scraper/contract.py`](src/scraper/contract.py) (`IngestRecord`).

| What | Where |
|---|---|
| Normalized output | `./storage/ingest/normalized/<source>.ndjson` (one JSON object per line) |
| Raw snapshot | `./storage/ingest/raw/<source>/<hash>.<ext>` |
| Committed demo slice | `./storage/ingest/samples/<source>.ndjson` |
| Laravel reads it via | `php artisan ingest:run --source=<x>` (idempotent upsert on `natural_key`) |

Each NDJSON line:
```json
{"source":"ted","natural_key":"387269-2026","source_url":"https://ted.europa.eu/...","fetched_at":"2026-06-05T18:13:52Z","schema_version":1,"payload":{}}
```
`./storage/ingest` is shared (bind-mounted) between this scraper and the Laravel `app` container.

## Architecture

```
config.py    env + SSRF allow-list        sinks/ndjson.py   normalized + raw + samples
http.py      polite client (UA, throttle, retry, robots, allow-list, cache)
encoding.py  bytes -> chardet -> cp1251/utf-8        normalize.py  money/date/CPV/EIK/text
registry.py  source id -> Source          run.py        CLI
sources/     base.py + one module per source (egov, caiseop, ted, aop, sebra, eop, isun)
```

Each source splits `fetch()` (network) from `parse()` (pure) so parsers are
unit-tested offline against committed fixtures — no live hits in CI.

## Sources

| id | Source | Tooling | Default state |
|----|--------|---------|---------------|
| `ted` | TED EU notices (BG) | httpx (JSON API v3) | works as-is; real sample committed |
| `egov` | data.egov.bg datasets | httpx (POST getResourceData) | set `EGOV_RESOURCES` |
| `caiseop` | ЦАИС ЕОП contracts CSV | httpx (CSV) | set `CAISEOP_CSV_URLS` |
| `aop` | АОП/РОП register | httpx + BeautifulSoup | set `AOP_PAGES` |
| `sebra` | budget payments | httpx (CSV) | set `SEBRA_CSV_URLS` |
| `eop` | app.eop.bg search | Playwright (`browser` extra) | set `EOP_PAGES` |
| `isun` | ИСУН 2020 EU-funds | Playwright (`browser` extra) | set `ISUN_PAGES` |

See [`.env.example`](.env.example) for every knob. Only the allow-listed source
domains are fetchable (SSRF guard); **no API keys / secrets are required.**

## Run it
```bash
cd apps/scraper && uv sync                 # add: --extra browser  for eop/isun
cp .env.example .env                        # then enable a source: TED_ENABLED=true

uv run scrape --list                        # show sources + enabled state
uv run scrape --source ted --limit 50       # scrape one source
uv run scrape --all                         # every ENABLED source

# docker (shares ./storage/ingest with the Laravel app)
docker compose run --rm scraper uv run scrape --source ted
# then, in the Laravel app:
docker compose exec app php artisan ingest:run --source=ted
```

## Semantic search (embeddings)
Vectors are produced here in Python; the backend stores them in pgvector. We
embed a composed searchable document (subject/title + entity names + CPV), not
just the title and not the raw blob. Default model:
`sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2` (384-dim, CPU via
fastembed ONNX). Output is a sidecar keyed by `natural_key`, so the ingest
contract is untouched. Full handoff: [`/SOURCES.md`](../../SOURCES.md).

```bash
uv sync --extra embed                          # fastembed (ONNX) + numpy

uv run embed --source ted                      # -> storage/ingest/embeddings/ted.ndjson
uv run search --source ted "компютърни монитори"   # cosine top-k demo (pure Python)
```
`EMBED_BACKEND`/`EMBED_MODEL` switch the backend (fastembed | sentence-transformers)
and model. No API keys — the model is public.

## Test it
```bash
uv run pytest                 # offline: parsers + embeddings vs fixtures/stub model
uv run pytest --run-network   # also hit live upstreams + download the real model
uv run ruff check .
```

## Non-negotiables (see scraping.md)
- Public data + allow-listed source domains only (SSRF guard).
- Cyrillic: bytes → `chardet` → `cp1251`/`utf-8` → emit UTF-8 (`ensure_ascii=False`).
- Idempotent on `natural_key`; keep raw + normalized; every record has `source_url` + `fetched_at`.
- Ingest-first; never hit upstream live in the demo; a real sample slice is committed.
- Polite: robots, throttle, UA, cache. Log "ingested N, skipped M (reasons)".
- Embeddings for semantic search are produced here (see above); the backend only
  stores + indexes them in pgvector.
