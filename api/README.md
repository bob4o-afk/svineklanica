# /api — Backend layer (the contract owner)

**Owner:** API owner · **Stack:** Python + FastAPI + Pydantic.

> Read [`/CONTEXT.md`](../CONTEXT.md) and [`/docs/master_rules.md`](../docs/master_rules.md) before writing.

## Your remit — you are the lynchpin
- Define **Pydantic response models** = the single source of truth for every type that crosses the API.
- Serve data the `/data` layer wrote into the `.duckdb` file (read-only from here).
- After changing a model, **regenerate the TS types** into `/shared/types` so `/web` stays in sync.

## The two seams you sit between
- **Seam 1 (data → api):** read the DuckDB file; respect the schema in [`../data/SCHEMA.md`](../data/SCHEMA.md). Don't assume columns — check it.
- **Seam 2 (api → web):** OpenAPI → TS types. Suggested: `openapi-typescript` against the running app's `/openapi.json` → `shared/types/api.ts`.

## Conventions
- `snake_case.py`, one-line docstring per function. Pydantic everywhere; no untyped dicts crossing the boundary.
- Validate inputs; return typed models, never raw dicts. Run (`uv run uvicorn main:app --reload`) before done.

## Scaffold (when the idea is chosen)
```bash
uv init . && uv add fastapi "uvicorn[standard]" pydantic duckdb
# type-gen (run /web side): npx openapi-typescript http://localhost:8000/openapi.json -o ../shared/types/api.ts
```
*(Empty shell for now — environment-only.)*
