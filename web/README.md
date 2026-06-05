# /web — Frontend layer

**Owner:** Driver · **Stack:** Vite + React + TypeScript + shadcn/ui + Recharts (+ WXT if a browser extension).

> Read [`/CONTEXT.md`](../CONTEXT.md) and [`/docs/master_rules.md`](../docs/master_rules.md) before writing.

## Your remit
- Build the UI / extension. Owns the **live demo**.
- Consume the API **only** through the generated typed client.

## The one hard rule (Seam 2)
- **Never hand-write a type that crosses the API.** Types come from `/api`'s OpenAPI schema → generated into `/shared/types`. Import from there.
- If you need a field that doesn't exist, ask the API owner to add it to the Pydantic model and regenerate — don't fake it client-side.

## Conventions
- Components `PascalCase.tsx`, other files `kebab-case.ts`. Strict TS (`strict: true`).
- **Bulgarian** in user-facing strings; **English** in code/identifiers/comments.
- Small diffs, run (`pnpm dev`) before calling it done. One-line `CHANGELOG.md` entry per merge.

## Scaffold (when the idea is chosen)
```bash
pnpm create vite@latest . -- --template react-ts   # then add shadcn/ui, recharts
# extension instead? use WXT: pnpm dlx wxt@latest init .
```
*(Empty shell for now — environment-only.)*
