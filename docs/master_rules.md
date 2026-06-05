# MASTER_RULES.md — The Engineering Charter

> The rules that make **3 people + 4 AI tools** produce code that fits together like a puzzle. `CONTEXT.md` is the short version every AI reads each prompt; this is the full reference. **Auto-enforced** wherever a machine can check it (your choice) — because a rule nobody checks is just a suggestion.
>
> Decisions baked in: **by-layer split** · **English code / Bulgarian UI** · **light+structured docs** · **balanced speed/robustness** · **auto-enforcement**.

---

## 0. The one idea that makes it all work
You split **by layer** (web / api / data). That only fits together if the **seams between layers are contracts, not conversations.** Two seams, two single-sources-of-truth:

| Seam | Source of truth | Rule |
|---|---|---|
| **data → api** | the **DuckDB table schema** (`/data/SCHEMA.md`) | Don't add/rename a column without updating SCHEMA.md in the same commit. |
| **api → web** | **Pydantic response models** → OpenAPI → generated TS types | Frontend never hand-writes a cross-API type. Change model → regenerate → import. |

If you respect the two seams, the three of you can work in parallel almost without collisions.

---

## 1. Naming standards (auto-checked by linters)

**Files**
- Python: `snake_case.py`. TS modules: `kebab-case.ts`. React components: `PascalCase.tsx`.
- One main export per file; file name matches it. Small + single-purpose (< ~200 lines; if bigger, split).

**Identifiers (English, descriptive, no abbreviations)**
- Functions = verb phrases: `fetch_sections()`, `computeFingerprint()`. 
- Variables/data = nouns: `sections`, `ownerByEik`.
- Booleans = `is/has/should`: `isFlagged`, `hasOwner`.
- Constants = `UPPER_SNAKE_CASE`. Types/classes = `PascalCase`.
- **No Cyrillic identifiers.** Bulgarian appears **only** inside user-facing string values (and a `bg.ts`/i18n map is preferred over inline).

**The cross-layer naming pact (critical for by-layer):** a concept has **one name everywhere**. A polling section is `section` in data, `Section` in the Pydantic model, `Section` in the TS type, `section` in the React prop. The join key is `eik` everywhere — never `eik` in one place and `company_id` in another. **Maintain a 1-line glossary in `MASTER_RULES.md` §8** so all three layers + all four AIs use identical words.

**Branches:** `layer/short-task` → `web/anomaly-map`, `api/sections-endpoint`, `data/cik-ingest`.
**Commits:** Conventional Commits — `type(scope): summary` → `feat(api): add /sections endpoint`. Types: `feat fix docs refactor chore test`.

---

## 2. Folder structure & ownership
```
/web      Frontend — OWNER: Driver
/api      Backend  — OWNER: (assign)
/data     Scraping/ingest — OWNER: Defender
/shared   generated types, openapi.json, *.duckdb, glossary
CONTEXT.md MASTER_RULES.md CHANGELOG.md README.md LICENSE
```
- **You may edit your own folder freely.** Editing another's folder → announce in team chat first (it usually means the *contract* needs to change — do that deliberately).
- `/shared` is touched only via the **generation scripts**, never hand-edited.

---

## 3. The contract workflow (do this, exactly)
**Backend owns the API shape:**
1. Define / change a **Pydantic model** in `/api`.
2. FastAPI auto-exposes `/openapi.json`.
3. Run `pnpm gen:types` (script: `openapi-typescript /shared/openapi.json -o /shared/types.ts`).
4. Frontend imports from `/shared/types.ts`. **Done — types can't drift.**

**Data owns the DB shape:** every table + column documented in `/data/SCHEMA.md` with type + 1-line meaning. The API reads only documented columns.

> When a contract changes, the owner **posts one line in chat**: *"contract: added `Section.turnout: float`"*. That's the only synchronous coordination you need.

---

## 4. Documentation rules (light + structured)
- **Every function:** one-line docstring (what + any gotcha). No essays.
- **Every folder:** a `README.md` — what it is, how to run it, key files (3–6 lines).
- **Every merge:** one line in `CHANGELOG.md` (`## [time] scope — what changed`). This is your team's shared memory + navigation map.
- **The contract files** (`SCHEMA.md`, the glossary) are docs too — keep them current in the *same commit* as the change.
- Comments explain **why**, not what. AI-generated code especially: delete the obvious "# increment i" noise.

---

## 5. Git workflow (recommended — you said "not sure")
For a by-layer split with clean folder boundaries, conflicts are rare. Use the lightest safe flow:

- **Short branch per task** (`layer/task`), **merge to `main` fast** once it type-checks and runs. No formal PR review — too slow for 48h — but **glance at the diff** before merging.
- **Commit small + often** (every working increment). Small commits = trivial conflict resolution.
- **`main` must always run.** Broke main? Fix-forward immediately or revert; tell the team.
- **The contract files are the only real conflict risk** — when you change one, announce it and merge it quickly so others rebase onto it.
- **Pull `main` before starting a task** and before merging.
- Optional power move: **git worktrees** so each person/AI has an isolated checkout of the same repo (avoids stepping on each other).

---

## 6. Definition of Done (the merge gate)
A task is done only when **all** are true:
- [ ] It **runs** (you executed it, not just generated it).
- [ ] **Type-check passes** (`tsc --noEmit` / `pyright`) and **linter/formatter clean** (auto-run, see §7).
- [ ] Cross-layer change? **Contract regenerated + SCHEMA/glossary updated** in the same commit.
- [ ] One-line **docstring** + **CHANGELOG** entry added.
- [ ] On the **demo-critical path**? A **tiny test** exists (balanced mode — skip tests elsewhere).
- [ ] No new dependency added without team OK. No Cyrillic identifiers. No secrets.

---

## 7. Auto-enforcement setup (do this in Phase 0 — machine enforces, not willpower)
Install once, then consistency is automatic across all 3 people + 4 AIs:

**Frontend (`/web`)**
- **Prettier** (format on save) + **ESLint** (typescript-eslint) — shared `.prettierrc` + `.eslintrc`.
- **`tsconfig.json`: `"strict": true`** (non-negotiable — types are your AI safety net).

**Backend/Data (`/api`, `/data`)**
- **Ruff** (format + lint, one fast tool) — shared `ruff.toml`.
- **Pyright** (or mypy) for type-checking; type-hint everything; Pydantic for all I/O shapes.

**Repo-wide**
- **`pre-commit`** hooks running Prettier/ESLint/Ruff/type-check on changed files → bad code can't even be committed.
- **EditorConfig** (`.editorconfig`) so every editor/AI uses the same indentation/encoding (**UTF-8**, LF).
- One command to run it all: `pnpm check` (format + lint + types). The Definition of Done = "`pnpm check` is green."

> Tell every AI in CONTEXT.md: *"Run `pnpm check` and fix all errors before declaring done."* Now the machine reviews the AI for you.

---

## 8. Glossary (single source of truth for names — keep it tiny + current)
| Concept | Canonical name | Notes |
|---|---|---|
| Polling section | `section` / `Section` | CIK section, EKATTE-linked |
| Company ID | `eik` | the universal join key; never `company_id` |
| Media outlet | `outlet` | СЕМ entity |
| Beneficial owner | `owner` | |
| Anomaly score | `anomalyScore` / `anomaly_score` | |
| *(add as you go)* | | |

---

## 9. Rules specifically for parallel AI work (4 tools, 3 people)
- **One writer per hotspot file.** Two AIs must never edit the same file at once — assign it.
- **Each AI gets a layer/folder**, mirroring the human owner. Cross-layer = human decision.
- **All tools read the same `CONTEXT.md`** (symlink CLAUDE.md/GEMINI.md/AGENTS.md/.cursorrules → it). Update CONTEXT.md, not four files.
- **Sequential merges**, not simultaneous — merge one branch, others pull, repeat.
- **Plan-then-code** for anything touching a seam: have the AI list files + the contract change, eyeball it, then let it run.
- If two people need the same contract changed, **the layer that owns it makes the change**; the other consumes it.

---

## 10. The 30-second "are we still fitting together?" check (run each gate)
1. Does `main` run? 2. Is the contract regenerated & consistent? 3. Any duplicate names for the same concept? 4. CHANGELOG current? 5. `pnpm check` green? If all yes — keep moving. If not — fix before adding anything new.
