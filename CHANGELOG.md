# Changelog

One line per merge. Newest at the top. Keep it terse: `- <layer>: <what changed>`.

## Setup
- repo: **adopted the Laravel + Postgres + Docker procurement-watchdog stack** as the base (replaces the earlier FastAPI/DuckDB shell). See `MERGE_REPORT.md`.
- scraping: added the **Python scraping lane** (`apps/scraper`) + the NDJSON ingest contract (`.claude/rules/scraping.md`, `contract.py`, compose `scraper` service, `./storage/ingest/`).
- agents: 3-lane ownership (frontend/backend/scraping) with per-lane `CLAUDE.md`/`AGENTS.md`; all-4-tools pointers (`GEMINI.md`/`AGENTS.md`/`.cursorrules`); `.claude/settings.json` allowlist for the new toolchain.
- docs: ported `ai_usage_guide.md` + `prompt_library.md`; archived earlier-stack research under `docs/research/`.
