# Prompt Library — Copy-Paste, Low-Error

> Ready prompts for the common tasks, so the whole team prompts the AI **consistently** and triggers the low-error behaviors (context, version pins, typed shapes, run/test, small diffs). Adapt the `[brackets]`.
>
> **Every prompt assumes the AI has read `CLAUDE.md` + the relevant `.claude/rules/` file.** If a tool doesn't auto-load them, paste: *"Read CLAUDE.md and .claude/rules/ first."*

---

## Universal preamble (prepend to any task)
```
Follow CLAUDE.md + .claude/rules/. Stay in your lane (frontend=apps/web, backend=repo root, scraping=apps/scraper).
Stack is pinned — don't add deps or invent APIs. English code, Bulgarian only in user-facing strings.
Plan the files you'll touch first, then code, then RUN it (in Docker) and show me the output.
Small diff, one concern. If unsure or missing context, ASK — don't guess.
```

## Backend — new module/endpoint (`/`, Laravel) — contract-safe
```
In modules/[Domain], add GET /api/[path] following .claude/rules/backend.md:
Route (guarded: auth:sanctum or public+throttle) → thin Controller → [Name]Data DTO (Spatie laravel-data, validates+authorizes)
→ [UseCase]Action → [Name]Repository (the ONLY place touching Eloquent) → [Name]Resource.
Expose public_id (UUID), never the autoincrement id. Add #[TypeScript] to the DTO/Resource/Enum.
No business logic in the controller. Then run `make test-be` and show me the passing feature test
(include an unauthorized-access test proving the guard works).
```

## Backend — new red-flag detector
```
Add a [Name]Detector in modules/Detection per .claude/rules/backend.md §11.
Single run() reads via repositories, writes Flag rows: type, severity, subject, source_url, explanation_bg, evidence.
A flag with no source_url is forbidden. Make it deterministic + re-runnable, dispatched as a queued Job.
Add a Pest smoke test on a tiny real fixture. Run `make test-be`.
```

## Backend — new migration / ingest upsert
```
Create a migration for table [name] (per .claude/rules/backend.md §12).
Ingest must be idempotent: upsert on the natural key [tender no / TED id / EIK].
Then update the `ingest:run` mapping for source [x] so it reads ./storage/ingest/normalized/[x].ndjson
(the scraper's contract) and upserts. Run `make migrate` then `php artisan ingest:run --source=[x]` on the sample slice.
```

## Backend → frontend type sync (Seam 2)
```
I changed a DTO/Resource. Run `composer sync:api-types` (php artisan typescript:transform) to regenerate
the TS types, then tell the frontend which generated type to import. Don't hand-write the interface.
```

## Frontend — new App* component (`apps/web`)
```
Create App[Name] in apps/web/src/components per .claude/rules/frontend.md.
One component per file; export App[Name]Props. Use MUI (+ MUI X if a grid/chart) with the THEME/tokens —
no hardcoded colors/values; Tailwind only for layout. All strings via i18n t() (Bulgarian-first).
Network only through lib/http; icons as Phosphor XxxIcon; show a loading skeleton + error state.
Import API types from the generated types (Seam 2) — never hand-roll them. Then run `make test-fe` (or pnpm dev) and confirm it renders.
```

## Scraping — new source (`apps/scraper`, Python)
```
Add a source module apps/scraper/src/scraper/sources/[x].py per .claude/rules/scraping.md.
Fetch ONLY from the allow-listed domain [base_url] with httpx (Playwright only if JS-rendered); be polite (UA, throttle, robots).
⚠️ Cyrillic: read raw bytes → chardet → decode (cp1251 for legacy gov, else utf-8) → keep UTF-8.
Map raw → IngestRecord (contract.py): source, natural_key=[stable key], source_url, fetched_at (UTC), payload.
Write normalized NDJSON to ./storage/ingest/normalized/[x].ndjson + a raw snapshot under raw/[x]/.
Save a small real slice to samples/[x].ndjson. Print "ingested N, skipped M (reasons)" + 5 rows so I can eyeball the Cyrillic.
```

## Bug fix
```
Bug: [what happens] vs [expected]. File: [path]. Error: [paste].
Find the root cause first and explain it in one line BEFORE changing code.
Make the smallest fix. Run it (in Docker) and show it's fixed. Don't refactor unrelated code.
```

## Refactor / cleanup (use sparingly in a sprint)
```
Refactor [file] to [goal] WITHOUT changing behavior. Keep the same public interface / types / contract.
Small steps, run after each (`make test`). If anything's ambiguous, ask before proceeding.
```

## "Explain / where is" (use Gemini — huge context)
```
Read [folder]. Explain how [feature] works and list the exact files + classes involved.
Don't change anything — read-only. Output a short map.
```

## Pitch / copy help (allowed — not code)
```
Here's what our tool does + the real data: [..]. Write 3 versions of a 1-sentence punk cold-open
in Bulgarian: a number/contradiction + a pause + "here they are." Fact-based, sharp, no naming private individuals.
```

---

## The low-error habits (baked into the prompts above)
1. Point at **exact files/lanes**, don't dump the repo. 2. **Use approved libs only**; no invented APIs.
3. Constrain to **typed shapes** (DTO/`#[TypeScript]`/Pydantic contract). 4. **Plan → code → run (in Docker)**. 5. **Small diffs, one concern**.
6. "**Ask, don't guess.**" 7. Tell it the **Cyrillic quirk** (chardet/cp1251). 8. Make it **show output / passing test**.
9. **Guarded + sourced**: every endpoint guarded, every flag has a `source_url`. 10. After a seam change, **regenerate the contract** (`composer sync:api-types` / update `ingest:run`).
