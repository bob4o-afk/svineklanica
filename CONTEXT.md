# CONTEXT.md — read this first, every session

> Shared brain for **every AI tool** (Claude Code, Cursor, Gemini, Codex/Copilot) and every teammate.
> The root files `CLAUDE.md`, `GEMINI.md`, `AGENTS.md`, `.cursorrules` are thin pointers to this file — so every tool reads the same thing. Keep this **short** (it loads every prompt). Full detail lives in `docs/master_rules.md`.

---

## What we're building
LiberHack 2026 project — **Bulgarian civic-tech, "punk"**: expose what's broken in real BG institutions with real public data. Local-first, open-source (**GPL-3.0**). Final idea decided at the event; leading candidate: a **person/sanctions + media-ownership "X-ray" browser extension**; backups: election-anomaly tool, media-ownership map.

## 🔑 Golden rules (break these and the puzzle stops fitting)
1. **English in code, Bulgarian only in user-facing UI strings.** Identifiers, comments, commits = English. No Cyrillic identifiers.
2. **The contract is law.** Backend Pydantic models are the single source of truth → they generate the TS types the frontend uses. Never hand-write a type that crosses the API. Change the model, regenerate, don't fork.
3. **Only approved libraries** (list below). Do **not** add a dependency or invent an API without asking — ~20% of AI-suggested packages don't exist.
4. **Cyrillic = UTF-8 always.** Read bytes → detect (`chardet`) → decode (`cp1251` for legacy gov sites, else `utf-8`) → store UTF-8. `json.dump(..., ensure_ascii=False)`. Spot-check `ще/ъ/я`.
5. **Small diffs, run before "done."** One change at a time; run it; only then move on. Never hand back code you didn't execute.
6. **Stay in your layer's folder** (see structure). Editing another layer? Say so in chat first.

## Stack (pin these; don't substitute)
- **Frontend** (`/web`): Vite + React + **TypeScript** + shadcn/ui + Recharts (+ WXT if it's a browser extension)
- **Backend** (`/api`): Python + **FastAPI** + Pydantic
- **Data** (`/data`): Python scraping (httpx + BeautifulSoup → Playwright only if JS-rendered) → **DuckDB**
- **DB**: DuckDB single file (queries CSVs directly). SQLite only if app-state needed.

## Repo structure & the two seams
```
/web      React+Vite+TS         ← consumes typed API client
/api      FastAPI + Pydantic    ← single source of truth for API types
/data     scraping → DuckDB     ← writes the .duckdb file /api reads
/shared   generated TS types, OpenAPI schema, the .duckdb file
/docs     master_rules, ai_usage_guide, prompt_library, agent_setup
CONTEXT.md   CHANGELOG.md   LICENSE (GPL-3.0)
```
- **Seam 1 (data → api):** the **DuckDB table schema**. Documented in `/data/SCHEMA.md`. Don't rename a column without updating it.
- **Seam 2 (api → web):** **OpenAPI → TS types**. Backend defines Pydantic response models; run the type-gen script; frontend imports the generated types into `/shared`. This is what makes the layers fit.

## Conventions (summary — full in docs/master_rules.md)
- **Files:** small + single-purpose. `kebab-case.ts` / `snake_case.py`. React components `PascalCase.tsx`.
- **Names:** descriptive, English, no abbreviations. Functions = verbs (`fetchSections`), data = nouns.
- **Docs:** one-line docstring per function + a `README.md` per folder + update `CHANGELOG.md` per merge.
- **Types:** strict TS (`strict: true`), Pydantic everywhere. A red type error = the AI was wrong; fix it, don't suppress.
- **Tests:** tiny test only on the **demo-critical path** (balanced mode). Skip elsewhere.

## How to behave as an AI agent here
- **Read this file + your folder's `README.md` + the relevant SCHEMA/contract before writing.** Don't guess shapes — ask or read.
- **Plan, then code** for anything non-trivial: list the files you'll touch, get a nod, then edit.
- **Prefer editing existing files** over creating new ones; match the surrounding style.
- **After editing:** run it / type-check, then add a one-line `CHANGELOG.md` entry.
- **Never** commit secrets, add cloud dependencies, or use Cyrillic identifiers.

## Do NOT
- ❌ add deps / new tools without asking · ❌ hand-write cross-API types · ❌ Cyrillic in code · ❌ large multi-concern diffs · ❌ edit another layer's folder silently · ❌ assume UTF-8 on gov data without checking · ❌ leave code unrun.
