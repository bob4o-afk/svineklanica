# MERGE_REPORT — reconciling the two scaffolds

_What happened when we compared the friend's `liberhack` scaffold against our `corruption-fucker` scaffold, and every decision taken. Active spec is `CLAUDE.md` + `.claude/rules/`; this is the audit trail._

## TL;DR — the decision
Two **incompatible** foundations existed for the same hackathon. We **adopted the friend's stack wholesale** as the base and ported in what was genuinely new from ours.

| Decision | Choice |
|---|---|
| Foundation | **Friend's** Laravel + Postgres + Redis + Docker/K8s stack |
| Idea | **Procurement watchdog** → broadened framing: *"scrape & publish likely-corrupt activity"* (procurement = flagship) |
| Infra scope | **Keep all of it** (CI/CD, K8s, monitoring, honeypot, auth) |
| Backend / scraping | **Backend = PHP/Laravel; scraping = Python** (`apps/scraper`) ← the one real architectural addition |
| License | **Kept our GPL-3.0** (friend's was MIT) — ⚠️ open team decision |

## The two inputs
- **Ours (`corruption-fucker`):** Python/FastAPI/Pydantic, DuckDB, React+shadcn, local-first, GPL. Idea-flexible (sanctions/media X-ray; election POC).
- **Friend's (`liberhack`):** PHP/Laravel 11 (modular DDD), Postgres+Redis, React+MUI+Tailwind PWA, Docker+K8s+CI/CD+monitoring+honeypot+Sanctum, MIT. Idea-specific (procurement watchdog). Config-only, no app code (same honest "build on-site" stance).

These could not be "appended together" — you can't have two backends, two DBs, or two component libraries. So it was a **fork decision**, resolved above, then a one-directional port.

## ✅ NEW — appended (didn't exist in the friend's repo)
- **Python scraping lane** — `.claude/rules/scraping.md`, `apps/scraper/` (pyproject, Dockerfile, `.env.example`, `contract.py` = the `IngestRecord` seam, `run.py` stub, `sources/`), the `scraper` compose service (profile `scrape`), and `./storage/ingest/{raw,normalized,samples}/`. This is what makes "backend PHP, scraping Python" real: Python emits NDJSON → `php artisan ingest:run` upserts.
- **Multi-tool AI config** — `GEMINI.md`, `AGENTS.md`, `.cursorrules` (root pointers) so all 4 tools read the same rules; plus **per-lane** `CLAUDE.md`/`AGENTS.md` in `apps/web` and `apps/scraper`.
- **Agent allowlist** — `.claude/settings.json` tuned to this toolchain (make/docker/composer/artisan/pnpm/uv/git auto; `rm`/`push`/`down -v`/`migrate:fresh`/secrets gated). Enables 3–4 agents with ~90% fewer prompts.
- **Playbooks** — `docs/ai_usage_guide.md` (token/limit tactics, multi-tool coordination) and `docs/prompt_library.md` (reframed to Laravel/MUI/Python prompts).
- **`docs/research/`** — our pitch playbook, rubric scorecard, README template, data-source intel, fact-check, and the **election-anomaly POC as a backup idea**.

## 🔀 MERGED / kept-one-canonical (both had it)
- **CLAUDE.md, README.md, .gitignore** → kept the **friend's** (authoritative for the chosen stack); edited for coherence (added scraping lane + 3-lane/2-seam map + AI-agent section; `.gitignore` gained Python + ingest-output rules).
- **Engineering rules** → kept the friend's `.claude/rules/*` (more complete, stack-correct); added two one-line notes (in `backend.md` §3 and `data-sources.md`) clarifying scraping is Python.
- **License** → kept **our GPL-3.0** over the friend's MIT (see open items).

## 🗑️ SUPERSEDED — removed or archived
- Removed the FastAPI/DuckDB shells: `web/ api/ data/ shared/ CONTEXT.md` (replaced by Laravel root + `apps/web` + `apps/scraper`).
- Archived `docs/master_rules.md` + `docs/agent_setup.md` → `docs/research/*_OLD_STACK.md` (stack specifics now wrong; kept for the process bits).

## 🧩 How it fits — 3 lanes, 2 seams
```
scraping (Python, apps/scraper) ──NDJSON contract──▶ backend (Laravel, root) ──#[TypeScript]──▶ frontend (React, apps/web)
```
Each teammate owns one lane (own `CLAUDE.md`/`AGENTS.md`); the seams are the only coupling, so work stays parallel.

## ⚠️ Open items / honest risks (decide as a team)
1. **License: GPL-3.0 vs MIT.** We kept GPL; the friend's docs said MIT. Pick one, make `CLAUDE.md §2.5` match `LICENSE`.
2. **Scope vs time (your #1 weakness).** You chose to keep *all* the infra (K8s, monitoring, honeypot, CI/CD, full auth). Most of it scores ~0 on the rubric and is large surface for 48h. Recommend: stand up only `make up` + migrate + one real source + the demo path first; treat K8s/monitoring/honeypot as stretch.
3. **PHP as a third language** for an AI-driven team — Laravel's modular-DDD conventions are a lot for an LLM to get right; lean hard on `.claude/rules/backend.md` + `make test-be` to catch drift.
4. **Procurement is a crowded space** (BIRD/Bivol) and owner-links are paywalled — `data-sources.md` routes around it (TED + data.egov first). Keep the angle (the *detectors*) fresh.

## ▶️ Next steps
1. Generate the Laravel + Vite skeletons (`plan.txt` step 1). 2. `cp .env.example .env` (both) + `make build && make up && make install && make migrate`. 3. Wire **one** real source end-to-end (scraper → `ingest:run` → a Flag → a screen). 4. Commit a real `samples/*.ndjson` so the demo survives a dead upstream.
