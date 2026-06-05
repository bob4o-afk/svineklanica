# corruption-fucker 🤘

> **LiberHack 2026** · Bulgarian civic-tech, *but make it punk*. Expose what's broken in real BG institutions with real public data — sharp, fact-backed, legal, open-source.

Final idea is decided at the event. This repo is the **shared build environment** for a 3–4 person, AI-assisted team working in parallel.

---

## 🧠 Start here (humans and AIs both)
1. **[`CONTEXT.md`](./CONTEXT.md)** — the single source of truth. Every AI tool (Claude Code, Cursor, Gemini, Codex/Copilot) reads it via the root pointer files (`CLAUDE.md`, `GEMINI.md`, `AGENTS.md`, `.cursorrules`).
2. **[`docs/master_rules.md`](./docs/master_rules.md)** — the full engineering charter (naming, contracts, git, Definition of Done).
3. Your **layer's `README.md`** (`web/`, `api/`, or `data/`).

## 🧩 How the layers fit (work in parallel without colliding)
Each person/agent owns **one layer**. They connect only through two well-defined **seams**, so 3–4 agents can build at once:

```
/web    React + Vite + TypeScript     →  consumes the typed API client
/api    FastAPI + Pydantic            →  THE source of truth for API types
/data   scraping (httpx/BS4) → DuckDB →  writes the .duckdb file /api reads
/shared generated TS types · OpenAPI schema · the .duckdb file
/docs   master_rules · ai_usage_guide · prompt_library · agent_setup
```

- **Seam 1 — `data → api`:** the DuckDB **table schema** (documented in `data/SCHEMA.md`). Don't rename a column without updating it.
- **Seam 2 — `api → web`:** **OpenAPI → TypeScript types**. `/api` defines Pydantic response models → run the type-gen script → `/web` imports the generated types from `/shared`. **Never hand-write a type that crosses the API.**

> Golden rule: **stay in your layer's folder.** Need to touch another layer? Say so in chat first. Because the seams are explicit, you rarely need to.

## 👥 Suggested ownership (by layer)
| Agent / person | Owns | Also responsible for |
|---|---|---|
| Driver | `/web` | the live demo |
| API owner | `/api` | the contract (Pydantic models) — the lynchpin |
| Defender | `/data` | scraping, Cyrillic/UTF-8, data provenance for Q&A |

## ⚙️ Working in parallel — the rules
- **Short branches, fast merges.** Commit every few minutes (`git commit -m "wip: ..."`). Git is your undo button (`git restore` / `git revert`).
- **Allowlist is set** (`.claude/settings.json`): safe commands (`pnpm`, `uv`, `python`, `git add/commit`, type-checkers) run without prompts; `rm`, `git push`, `curl`, secrets stay manual. See [`docs/agent_setup.md`](./docs/agent_setup.md).
- **English in code, Bulgarian only in UI strings.** No Cyrillic identifiers.
- **Cyrillic = UTF-8 always** (detect with `chardet`, decode `cp1251` for legacy gov sites).
- **Small diffs, run before "done."** Add a one-line `CHANGELOG.md` entry per merge.

## 🚀 Getting set up (once code lands)
> Folders are currently **shells** (environment-only). The stack is pinned; scaffold inside each layer when the track is chosen.
```bash
# data  — scrape + build the DuckDB file
cd data && uv sync && uv run <ingest script>
# api   — serve the typed API
cd ../api && uv sync && uv run uvicorn main:app --reload
# web   — run the frontend
cd ../web && pnpm install && pnpm dev
```

## 📜 License
**GPL-3.0** (see [`LICENSE`](./LICENSE)) — copyleft: the rebellion stays open, and whoever continues it must keep it open too.
