# Frontend lane — apps/web

You own the **frontend** (one of the 3 lanes: frontend · backend · scraping).
Read [`/CLAUDE.md`](../../CLAUDE.md) + [`/.claude/rules/frontend.md`](../../.claude/rules/frontend.md) first.

- React + TypeScript + **MUI/MUI X** + Tailwind, mobile-first **PWA**. Reusable `App*` components.
- **Seam to backend:** consume the API only via the generated `#[TypeScript]` types (`composer sync:api-types`). Never hand-roll a cross-API type.
- BG-first UI, **no hardcoded strings** (i18n `t()`). One `http` wrapper, one `logger`, Phosphor `XxxIcon`.
- Stay in `apps/web`. Touching another lane? Say so in chat first.
- **Dev URL = `https://localhost`** (Caddy proxy), never `:5173` directly. Trust Caddy's root CA once + keep `VITE_HMR_DIRECT="false"` — setup, the HMR-mode table, and `ERR_SSL_PROTOCOL_ERROR` troubleshooting are in [`/.claude/rules/devops.md §1.1`](../../.claude/rules/devops.md).
