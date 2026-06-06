/**
 * The single import surface for API types across the app.
 * Now: the frontend-owned contract. Phase 7: flip to `./generated.d.ts` and reconcile.
 * Always `import type { ... } from '@/types/api'` — never from contract/generated directly.
 */
export type * from './contract';
