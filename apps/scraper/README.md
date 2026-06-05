# apps/scraper — Python scraping layer

The **scrapers are Python**; the **backend is Laravel**. This layer fetches public Bulgarian procurement data, normalizes it (Cyrillic-safe), and writes files. **Laravel never scrapes — it ingests these files.** Full rules: [`/.claude/rules/scraping.md`](../../.claude/rules/scraping.md).

> Config + the seam contract only right now. Source modules are written **on site** during the hackathon.

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
{"source":"ted","natural_key":"2024/S-123456","source_url":"https://ted.europa.eu/...","fetched_at":"2026-06-05T16:30:00Z","schema_version":1,"payload":{}}
```
`./storage/ingest` is shared (bind-mounted) between this scraper and the Laravel `app` container.

## Layout
```
apps/scraper/
  pyproject.toml          # uv project + deps
  Dockerfile              # python:3.12-slim; default CMD just prints usage
  .env.example            # INGEST_OUT_DIR, USER_AGENT, rate limits, per-source config
  src/scraper/
    contract.py           # IngestRecord (the seam) — the ONLY logic committed pre-event
    run.py                # CLI entrypoint (stub until on-site)
    sources/              # one module per source, added on site
    # added on site: normalize.py, sinks/ndjson.py
```

## Run it
```bash
# local (uv)
cd apps/scraper && uv sync
uv run scrape --source ted            # writes ./storage/ingest/normalized/ted.ndjson

# docker (shares ./storage/ingest with the Laravel app)
docker compose run --rm scraper uv run scrape --source ted

# then, in the Laravel app:
make up && docker compose exec app php artisan ingest:run --source=ted
```

## Non-negotiables (see scraping.md)
- Public data + allow-listed source domains only (SSRF guard).
- Cyrillic: bytes → `chardet` → `cp1251`/`utf-8` → emit UTF-8 (`ensure_ascii=False`).
- Idempotent on `natural_key`; keep raw + normalized; every record has `source_url` + `fetched_at`.
- Ingest-first; never hit upstream live in the demo; commit a real sample slice.
- Polite: robots, throttle, UA, cache. Log "ingested N, skipped M (reasons)".
