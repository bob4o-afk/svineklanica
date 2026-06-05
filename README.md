# LiberHack — Public Procurement Watchdog

Civic-tech, but punk: we ingest Bulgarian public-procurement data and automatically
raise **red flags** (price discrepancies, rigged specs, serial winners, cancelled-after-award,
implausible scope, late payments, cloned docs) — each linked to its primary source.

> **This repo is currently config/skeleton only — no application code yet.**
> Read **`plan.txt`** to bootstrap it, and **`CLAUDE.md`** + **`.claude/rules/`** for the rules.

## Layout (leha-style monorepo)
- **The Laravel API is the repo root** — its code lives in `app/` and `modules/<Domain>/`, config in `config/`. Built by the root `Dockerfile` → `liberhack/api` image.
- **`apps/web/`** — the React + TypeScript + **MUI** (incl. MUI X) + **Tailwind** + Phosphor client. One mobile-first responsive **PWA** = both the web and the "mobile" experience (no separate native app). Built by `apps/web/Dockerfile` → `liberhack/web` image.

## Stack
- **Backend:** API-first Laravel 11, modular (controllers · DTOs · actions · services · repositories), queued jobs, Sanctum auth, **PostgreSQL + `pgvector` (vectorized DB)** + Redis.
- **Infra:** Docker Compose (Caddy proxy, app, queue worker, scheduler, web, db, redis, mailpit). CI on GitHub Actions.

## Quick start
```bash
cp .env.example .env            # root = Laravel env + compose vars
cp apps/web/.env.example apps/web/.env
# then follow plan.txt step 1 to generate the framework skeletons
make build && make up && make install && make migrate
```
App via the proxy at `https://localhost`. Mailpit (captured emails) at `http://localhost:8025`.

See **`plan.txt`** for what you must set up externally (email provider, HTTPS, MUI X license) and where to get it.

---

## The idea
**corruption-fucker** — a website that **scrapes and publishes likely-corrupt activity** in Bulgaria, each claim linked to its primary source. **Public procurement is the flagship vertical** (the red-flag detectors), and the pipeline generalizes to other sources over time.

## Three lanes, one job each (work in parallel)
The team splits **by lane** so everyone owns their turf and nobody collides:

| Lane | Folder | Stack | Owner reads |
|---|---|---|---|
| **Frontend** | `apps/web` | React + TS + MUI + Tailwind (PWA) | `apps/web/CLAUDE.md` + `.claude/rules/frontend.md` |
| **Backend** | repo root (`app/`, `modules/`) | Laravel 11 (modular) | `CLAUDE.md` + `.claude/rules/backend.md` |
| **Scraping** | `apps/scraper` | **Python** (uv) | `apps/scraper/CLAUDE.md` + `.claude/rules/scraping.md` |

They connect through **two seams** — keep to them and the lanes fit like a puzzle:

```
scraping (Python)  ──NDJSON ingest contract──▶  backend (Laravel)  ──#[TypeScript] types──▶  frontend (React)
apps/scraper        ./storage/ingest/*.ndjson    repo root           composer sync:api-types   apps/web
```
- **Seam 1 — scraping → backend:** the Python scraper writes `IngestRecord` NDJSON to `./storage/ingest/normalized/`; Laravel runs `php artisan ingest:run --source=<x>` to idempotently upsert. Defined in `apps/scraper/src/scraper/contract.py` + `.claude/rules/scraping.md`.
- **Seam 2 — backend → frontend:** Laravel DTOs/Resources carry `#[TypeScript]`; `composer sync:api-types` generates the types `apps/web` imports. Never hand-write a cross-API type.

## Working with AI agents (all 4 tools)
- **One brain, every tool:** `CLAUDE.md` + `.claude/rules/` are authoritative. `GEMINI.md` / `AGENTS.md` / `.cursorrules` are thin pointers so Claude Code, Cursor, Gemini, and Codex/Copilot read the same rules. Each lane folder has its own `CLAUDE.md`/`AGENTS.md` for scoped context.
- **Allowlist:** `.claude/settings.json` auto-runs safe commands (`make`, `docker compose`, `composer`, `php artisan`, `pnpm`, `uv`, `git add/commit`, linters/tests) and **gates** the dangerous ones (`rm`, `git push`, `docker compose down -v`, `migrate:fresh`, secrets). ~90% fewer prompts, near-zero risk.
- **Playbooks:** `docs/ai_usage_guide.md` (token/limit tactics, multi-tool coordination) and `docs/prompt_library.md` (copy-paste, low-error prompts per lane). Background/reference research in `docs/research/`.

## Run the scraper (Python lane)
```bash
# on-demand; shares ./storage/ingest with the Laravel app
docker compose run --rm scraper uv run scrape --source ted
docker compose exec app php artisan ingest:run --source=ted
```

