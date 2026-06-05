# Backend rules (Laravel, API-first) — standards

> ⭐ = MUST follow, no exceptions. The rest are strong defaults.
> The backend is an **API** (JSON) consumed by both the web and mobile clients. **No Inertia** — both clients hit the same guarded API.
> **Abstraction & reuse are the goal.** Program to interfaces, extract shared logic, never copy-paste a flow.

## 1. Modular architecture ⭐

- Code is organized into **modules / bounded contexts** under `modules/<Domain>/` (e.g. `Procurement`, `Detection`, `Identity`, `Notifications`). Each module owns its `Controllers`, `Actions`, `Services`, `Repositories`, `Data` (DTOs), `Models`, `Policies`, `Jobs`, routes, and tests.
- **No direct cross-module imports.** A class in `Modules\X` must not `use` a concrete class from `Modules\Y`. Cross-module communication goes through:
  - **Shared contracts** — interfaces in a shared kernel (`App\Shared\Contracts\*`) returning **DTOs/snapshots** (`App\Shared\DTO\*`), or
  - **Events** — a module emits an event; the consuming module listens (queued listener).
- Each module binds its **repository interfaces → implementations** in its own service provider.

## 2. Request pipeline (the layering) ⭐

```
Route (guarded) → Controller (thin) → Data (DTO: validates + authorizes)
  → Action (single use case) / Service (orchestration)
  → Repository (the ONLY place that touches Eloquent)
  → Job (queued, for anything slow)  →  JsonResource (response)
```

- **Controllers are thin:** take a `Data` DTO, call one `Action`/`Service`, return a `Resource`. No business logic, no queries.
- **Repositories are the only place that touches Eloquent.** Everything else depends on a repository **interface**, not a model.
- **Actions = one use case each** (`IngestTenderAction`, `RunPriceDiscrepancyDetectorAction`). Services orchestrate multiple actions.

## 3. Async by default for anything slow/heavy ⭐

**If an operation is slow or expensive, it MUST be a queued Job — never run inline in the request.** This includes, at minimum:
- **Sending email / notifications.**
- **Creating a big aggregate with many children** (a post + its many child records, bulk inserts).
- **Ingesting** a source. (NB: the *scraping* itself is **Python** in `apps/scraper` — see [`scraping.md`](scraping.md). Laravel's job is `php artisan ingest:run`, which reads the scraper's NDJSON and upserts. Bulk upserts run as a queued Job.)
- **Running a detector** over the full dataset; generating exports/reports.

Rules:
- Heavy work → `...Job implements ShouldQueue`, dispatched from the Action. The request returns fast (202 / a "processing" resource).
- Use a real queue driver (Redis). Jobs are **idempotent** and **retry-safe**; set sensible `tries`/`backoff`; failures go to `failed_jobs` + are logged with context.
- Side effects fan out via **queued event listeners**, not inline calls.

## 4. Every endpoint is guarded ⭐

- **No unguarded route exists.** Every route sits behind middleware: authentication (Sanctum tokens for API), `throttle` (rate limit), and **policy authorization** on the action.
- Authorization is enforced in the `Data::authorize()` / a `Policy` — **never** inline `Gate::allows()` in a controller.
- Public read-only endpoints (the citizen-facing browse) are still rate-limited and abuse-guarded. See `.claude/rules/security.md` — it is part of these rules, not optional.

## 5. Mandatory class suffixes ⭐

`...Controller`, `...Action`, `...Service`, `...Repository`, `...Data`, `...Resource`, `...Policy`, `...Job`, `...Event`, `...Listener`, `...Middleware`. Add `...Detector` for red-flag detectors. One responsibility per class.

## 6. DTOs, not FormRequests ⭐

- Input is a **Spatie Laravel-Data** DTO that validates and authorizes. **No `FormRequest`** in new code.
- All input is validated AND sanitized at this boundary (see security.md §input).

## 7. Public IDs externally ⭐

- Anything exposed via the API gets a `public_id` (UUID) column; `getRouteKeyName()` returns `'public_id'`. **Never serialize the auto-increment `id`.** (Purely internal scratch tables are exempt.)

## 8. Logging — through one service ⭐

- Wrap logging in a `LoggingService` (operational). **No bare `Log::` calls** scattered across the codebase.
- **Never silently swallow exceptions.** No `catch (QueryException) {}`, no `catch (\Throwable) { return null; }`. Log with context and let real failures surface (503 for DB-down).
- Scrapers/jobs log skipped/failed records with reasons, so we can honestly report "ingested N, skipped M".

## 9. Type sync with the frontend ⭐

- Every DTO / Model / Resource / Enum reachable from a controller carries `#[TypeScript]`; generate TS types before wiring a new endpoint into a client. **Never hand-roll** a TS interface that mirrors a PHP shape.

## 10. i18n

- Every user-facing string (incl. exception messages and API error messages) goes through `__('key')`. UI is **Bulgarian-first**; keep keys translatable. Operational logs/console stay English.
- **Scraped data values stay as-is in Bulgarian** — content, never translated.

## 11. Detectors (project-specific)

- Each red-flag is its own `XxxDetector` with a single `run()` that reads via repositories and writes `Flag` rows (`type`, `severity`, `subject`, **`source_url`**, `explanation_bg`, `evidence`).
- A detector **never** asserts a flag without a `source_url`. Detectors are deterministic, re-runnable, and run **as queued jobs**; the UI reads precomputed flags.

## 12. DB & data integrity ⭐

- All DB access through Eloquent / the query builder — **parameterized only, never string-concatenated SQL** (see security.md).
- Ingest is idempotent: upsert on the source's natural key (tender registry number, TED notice ID, EIK).
- Migrations for every schema change; no manual DB edits.
- **Vectorized DB (`pgvector`) ⭐:** enable the extension in a migration; store embeddings as `vector` columns on the text we match on (item descriptions, tender docs, company names). Detectors query by **vector similarity** (overpricing clustering, doc-clone, shell/serial-winner entity matching) and a semantic-search endpoint uses it too. Index them (IVFFlat/HNSW). **Embeddings are produced in the Python layer, not PHP** (see `scraping.md`); how they reach Postgres (carried with the ingest record vs a Python embed step at ingest) is a wiring detail to finalize on-site — but don't recompute them in PHP.

## 13. Tests

- Pest, real Postgres (no DB mocking). A **smoke test per detector** on a small real fixture, a feature test per guarded endpoint (incl. an unauthorized-access test proving the guard works), and a test that a heavy operation **dispatches a job** rather than running inline.
- These run in CI on every push (see devops.md).
