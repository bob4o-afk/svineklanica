/** Mutable in-memory state for the MOCK admin flow (login, review queue, sources CRUD).
 *  The public read handlers also read approved flags from here, so approving a pending flag
 *  makes it appear in the citizen feed live — the demo's "watch it publish" moment.
 *  Seeded from the deterministic fixtures; `resetStore()` restores the seed (called between
 *  tests for isolation). No Date.now()/Math.random() — timestamps come from `daysAgoISO`. */
import type { AdminUser, FlagPost, ReviewDecision, Source } from '@/types/api';
import { flagPosts, sourceSeed } from './data';
import { daysAgoISO } from './factory';

/** The mock's only credentials — a single demo editor. */
const DEMO_EMAIL = 'admin@example.com';
const DEMO_PASSWORD = 'parola';

export const demoAdmin: AdminUser = {
  publicId: 'admin-1',
  name: 'Редактор',
  email: DEMO_EMAIL,
  isAdmin: true,
};

let flags: FlagPost[] = flagPosts.map((flag) => ({ ...flag }));
let sources: Source[] = sourceSeed.map((source) => ({ ...source }));
let authenticated = false;
let nextSourceId = sourceSeed.length + 1;

export function resetStore(): void {
  flags = flagPosts.map((flag) => ({ ...flag }));
  sources = sourceSeed.map((source) => ({ ...source }));
  authenticated = false;
  nextSourceId = sourceSeed.length + 1;
}

// --- Auth (session = a single module-level boolean; the mock has no real cookies) ---

export function tryLogin(email: string, password: string): AdminUser | null {
  if (email.trim().toLowerCase() === DEMO_EMAIL && password === DEMO_PASSWORD) {
    authenticated = true;
    return demoAdmin;
  }
  return null;
}

export function logout(): void {
  authenticated = false;
}

export function currentUser(): AdminUser | null {
  return authenticated ? demoAdmin : null;
}

// --- Flags ---

export function approvedFlagList(): FlagPost[] {
  return flags.filter((flag) => flag.status === 'approved');
}

export function pendingFlagList(): FlagPost[] {
  return flags.filter((flag) => flag.status === 'pending');
}

export function findFlag(publicId: string): FlagPost | null {
  return flags.find((flag) => flag.public_id === publicId) ?? null;
}

function mutateFlag(publicId: string, apply: (flag: FlagPost) => FlagPost): FlagPost | null {
  const index = flags.findIndex((flag) => flag.public_id === publicId);
  const current = flags[index];
  if (current === undefined) return null;
  const updated = apply(current);
  flags = flags.map((flag, i) => (i === index ? updated : flag));
  return updated;
}

export function approveFlag(publicId: string, decision: ReviewDecision): FlagPost | null {
  return mutateFlag(publicId, (flag) => ({
    ...flag,
    status: 'approved',
    published_at: daysAgoISO(0),
    ...(decision.title !== undefined && decision.title !== '' ? { title: decision.title } : {}),
    ...(decision.explanation_bg !== undefined && decision.explanation_bg !== ''
      ? { explanation_bg: decision.explanation_bg }
      : {}),
    ...(decision.tags !== undefined ? { tags: decision.tags } : {}),
  }));
}

export function rejectFlag(publicId: string): FlagPost | null {
  return mutateFlag(publicId, (flag) => ({ ...flag, status: 'rejected' }));
}

// --- Sources ---

export type SourceInput = Omit<Source, 'public_id' | 'last_ingested_at'>;

export function sourceList(): Source[] {
  return sources.slice();
}

export function createSource(input: SourceInput): Source {
  const created: Source = {
    public_id: `src-${nextSourceId}`,
    key: input.key,
    label: input.label,
    base_url: input.base_url,
    enabled: input.enabled,
    ...(input.notes !== undefined && input.notes !== '' ? { notes: input.notes } : {}),
  };
  nextSourceId += 1;
  sources = [created, ...sources];
  return created;
}

export function updateSource(publicId: string, patch: Partial<SourceInput>): Source | null {
  const current = sources.find((source) => source.public_id === publicId);
  if (current === undefined) return null;
  const updated: Source = { ...current, ...patch };
  sources = sources.map((source) => (source.public_id === publicId ? updated : source));
  return updated;
}

export function deleteSource(publicId: string): boolean {
  const before = sources.length;
  sources = sources.filter((source) => source.public_id !== publicId);
  return sources.length < before;
}
