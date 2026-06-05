/** Deterministic pseudo-randomness for stable mock fixtures (no Math.random / Date.now). */

export function mulberry32(seed: number): () => number {
  let a = seed;
  return () => {
    a |= 0;
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

export function pick<T>(arr: readonly T[], r: number): T {
  const idx = Math.floor(r * arr.length) % arr.length;
  const item = arr[idx];
  if (item === undefined) throw new Error('pick from empty array');
  return item;
}

export function intBetween(min: number, max: number, r: number): number {
  return Math.floor(min + r * (max - min + 1));
}

/** Fixed reference instant so fixtures are reproducible. */
const BASE_MS = Date.parse('2026-06-05T12:00:00Z');
const DAY_MS = 86_400_000;

export function daysAgoISO(n: number): string {
  return new Date(BASE_MS - n * DAY_MS).toISOString();
}
