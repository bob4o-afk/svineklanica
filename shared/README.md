# /shared — The seam artifacts

Cross-layer artifacts that connect the puzzle. **Do not hand-edit generated files** — regenerate them.

- `types/api.ts` — TypeScript types **generated** from `/api`'s OpenAPI schema (`openapi-typescript`). `/web` imports these. Regenerate after any Pydantic model change; never edit by hand.
- `openapi.json` — (optional) snapshot of the API schema.
- The `.duckdb` file may live here or in `/data` — whichever, `/api` reads it and `/data` writes it. Keep one canonical path and note it in `/data/SCHEMA.md`.

> If a type here is wrong, the fix is in `/api`'s Pydantic models, not here.
