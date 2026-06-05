# Prompt Library — Copy-Paste, Low-Error

> Ready prompts for the common tasks, so all 3 of you prompt the AI **consistently** and trigger the low-error behaviors (context, version pins, typed shapes, run/test, small diffs). Idea-agnostic. Adapt the `[brackets]`.
>
> **Every prompt assumes the AI has read `CONTEXT.md`.** If a tool doesn't auto-load it, paste: *"Read CONTEXT.md and MASTER_RULES.md first."*

---

## Universal preamble (prepend to any task)
```
Follow CONTEXT.md + MASTER_RULES.md. Stack is pinned — don't add deps or invent APIs.
English code, Bulgarian only in user-facing strings. Small diff, one concern.
Plan the files you'll touch first, then code, then RUN it and show me the output.
If unsure or missing context, ASK — don't guess.
```

## New React component (`/web`)
```
Create a [ComponentName] component in /web/src/components.
Props (TypeScript interface, import shared types from /shared/types.ts): [list].
It should [behavior]. Use shadcn/ui + Recharts where relevant. Labels in Bulgarian.
Keep it < 150 lines, one file. Add a 1-line docstring. Then run `pnpm -C web dev` and confirm it renders.
```

## New API endpoint (`/api`) — contract-safe
```
Add a FastAPI endpoint GET /[path] in /api returning a Pydantic model [Name] with fields [..].
Define/extend the Pydantic model FIRST (it's the source of truth). Read data from the DuckDB file.
After it works: remind me to run `pnpm gen:types` so /shared/types.ts updates, and add a CHANGELOG line.
Run uvicorn and show me the JSON from the endpoint.
```

## Scraper / ingest (`/data`)
```
Write a scraper in /data for [URL/source]. Use httpx + BeautifulSoup (Playwright only if JS-rendered).
⚠️ Bulgarian gov site: fetch raw bytes, detect encoding with chardet, decode (cp1251 for legacy else utf-8), normalize to UTF-8.
Parse fields [..] into a clean list of dicts, then load into a DuckDB table `[name]`.
Document the table columns in /data/SCHEMA.md. Print 5 sample rows so I can eyeball the Cyrillic. Don't hammer the site — add a small delay.
```

## DuckDB query / transform
```
In /data (or /api), write a DuckDB query that [aggregation/join].
Join on [key] (use the canonical name from the glossary). Read the CSV/txt directly if possible (no import).
Show the SQL + the first 10 result rows. Keep numbers as numbers (watch coded columns).
```

## Bug fix
```
Bug: [what happens] vs [expected]. Here's the file: [path]. Here's the error: [paste].
Find the root cause first and explain it in one line BEFORE changing code.
Make the smallest fix. Run it and show it's fixed. Don't refactor unrelated code.
```

## Refactor / cleanup (use sparingly in a sprint)
```
Refactor [file] to [goal] WITHOUT changing behavior. Keep the same public interface / types.
Small steps. Run after each step. If anything's ambiguous, ask before proceeding.
```

## "Explain / where is" (use Gemini — huge context)
```
Read [folder/repo]. Explain how [feature] works and list the exact files + functions involved.
Don't change anything — read-only. Output a short map.
```

## Pitch / copy help (allowed — not code)
```
Here's what our tool does + the real data: [..]. Write 3 versions of a 1-sentence punk cold-open
in Bulgarian: a number/contradiction + a pause + "here they are." Fact-based, sharp, no naming private individuals.
```

---

## The 10 low-error habits (baked into the prompts above)
1. Point at **exact files**, don't dump the repo. 2. **Pin versions** / "use only approved libs."
3. Constrain to **typed shapes** (Pydantic/TS). 4. **Plan → code → run**. 5. **Small diffs**.
6. "**Ask, don't guess.**" 7. Tell it the **Cyrillic quirk**. 8. Make it **show output**.
9. **One concern** per prompt. 10. After a seam change, **regenerate the contract**.
