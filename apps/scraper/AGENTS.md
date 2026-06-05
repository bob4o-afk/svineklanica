# Scraping lane — apps/scraper

You own **scraping** (one of the 3 lanes: frontend · backend · scraping).
Read [`/CLAUDE.md`](../../CLAUDE.md) + [`/.claude/rules/scraping.md`](../../.claude/rules/scraping.md) first.

- **Python** (uv). Fetch public BG data → normalize (Cyrillic-safe) → write the NDJSON ingest contract.
- **Seam to backend:** emit `IngestRecord` lines ([`contract.py`](src/scraper/contract.py)) to `./storage/ingest/normalized/<source>.ndjson`. Laravel ingests via `php artisan ingest:run` — **you never touch the backend or the DB.**
- Idempotent on `natural_key`; keep raw + normalized; every record has `source_url` + `fetched_at`. Public, allow-listed domains only. Polite + ingest-first.
- Stay in `apps/scraper`. Touching another lane? Say so in chat first.
