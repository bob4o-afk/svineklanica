# Scraping rules (Python, apps/scraper) — standards

> ⭐ = MUST follow, no exceptions. The rest are strong defaults.
> **Scraping is Python; the backend is Laravel.** This overrides the older assumption in `backend.md`/`data-sources.md` that scrapers were Laravel queued jobs. **Laravel never scrapes — it INGESTS the files this layer produces.**
> Public data only; authorized targets only (CLAUDE.md §2, security.md). Every flag downstream needs a source — so every record here carries one.

## 1. Where scraping lives ⭐

- All fetching/parsing/normalizing is **Python**, under `apps/scraper/` (a `uv` project). No scraping logic in the Laravel app.
- The boundary between the two languages is a **file contract** (§2). Python writes files; Laravel reads them. Neither imports the other.
- Run it out-of-band (CLI or the `scraper` compose service), **never** inside a web request and **never** during the live demo (§6).

## 2. The handoff CONTRACT (the third seam) ⭐

This is law — the Python side and the Laravel `ingest:run` command must agree on it exactly. The Pydantic source of truth is `apps/scraper/src/scraper/contract.py`.

- **Normalized output:** `./storage/ingest/normalized/<source>.ndjson` — one JSON object per line (NDJSON):
  ```json
  {"source":"ted","natural_key":"2024/S-123456","source_url":"https://ted.europa.eu/...","fetched_at":"2026-06-05T16:30:00Z","schema_version":1,"payload":{ "...normalized fields..." }}
  ```
- **Raw snapshot:** `./storage/ingest/raw/<source>/<hash>.<ext>` — the original payload (HTML/XML/JSON/PDF) kept so we can **re-parse without re-fetching** and **prove provenance**.
- **Demo snapshot:** `./storage/ingest/samples/<source>.ndjson` — a small, **real**, committed slice so a dead upstream can't kill the pitch (§6).
- **Laravel ingests** with: `php artisan ingest:run --source=<x>` — reads the NDJSON and **idempotently upserts** on `natural_key` (backend.md §12), through repositories only.
- `./storage/ingest` is bind-mounted into both the `scraper` service (at `INGEST_OUT_DIR=/data/ingest`) and the Laravel `app` container (at `/var/www/html/storage/ingest`).
- **The schema is a contract:** changing a `payload` field means updating `contract.py`, `SOURCES.md`, **and** the Laravel ingest mapping in the same change. Bump `schema_version` on a breaking change.

## 3. Cyrillic = UTF-8 always ⭐

- Read **bytes** → detect with `chardet` → decode **`cp1251`** for legacy gov sites, else **`utf-8`** → emit UTF-8 NDJSON with `json.dump(..., ensure_ascii=False)`.
- Spot-check `ще / ъ / я` render correctly before declaring a source clean. Mojibake in = mojibake in the demo.
- **Scraped values stay Bulgarian** — content, never translated (frontend.md §3).

## 4. Idempotency ⭐

- Pick a stable **natural key** per source: tender registry number, **TED notice id**, **EIK** (БУЛСТАТ) for companies. Put it in `natural_key`.
- Re-running a scrape must **not** create duplicates — the key is what Laravel upserts on. Same input → same `natural_key`.

## 5. Provenance on every record ⭐

- Every record has a real `source_url` (the page/document a human could open) and `fetched_at` (ISO-8601 **UTC**). No placeholder URLs.
- Keep **raw + normalized** both (§2). A flag whose source can't be reached is, on this theme, **disinformation = disqualifying** (data-sources.md §0).
- Maintain **`SOURCES.md`** at the repo root: each source, what we pull, when, format, robots/terms notes.

## 6. Ingest-first, polite, resilient ⭐

- **Ingest-first:** scrape → normalize to NDJSON → `ingest:run` into Postgres → serve from our DB. **Never hit upstream live during the demo.**
- **Polite scraper:** respect `robots.txt`, throttle (`RATE_LIMIT_RPS`), set a real `USER_AGENT`, cache aggressively. We expose bad actors; we don't become one.
- **SSRF guard (security.md §4):** only fetch from the **allow-list of known public source domains** (TED, data.egov.bg, АОП/РОП, SEBRA, registryagency). Never fetch an arbitrary user-supplied URL.
- **Tooling:** `httpx` + `BeautifulSoup`/`lxml` first; **Playwright only if the page is JS-rendered** (the `browser` optional dependency).

## 7. Honest logging

- Log skipped/failed records **with reasons**, so we can state ingest honestly: *"ingested N, skipped M because …"*. Never silently drop a row.
- A run prints a summary: source, fetched, written, skipped, output path.

## 8. No business logic committed before the event

- This folder ships **config + the contract only** until the hackathon. Source modules (`sources/<x>.py`), `normalize.py`, and `sinks/` are written **on site**. `contract.py` is allowed now because it *is* the seam definition, not logic.
