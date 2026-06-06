/** Robust price-outlier detection for the price-over-time chart. Picks the single capture that
 *  stands out as **overpriced** — the textbook overpricing signal (CLAUDE.md §1.1.1: "a laptop at
 *  10 in one order, 100 in another"). Used to highlight the outlier tender (frontend.md §10).
 *
 *  Median + MAD (median absolute deviation) — resistant to the very spike we're hunting for, unlike
 *  mean/std which a single huge value would drag along with it. Only points ABOVE the median count
 *  (we flag overpricing, not bargains). Returns `null` when nothing genuinely stands out (e.g. a
 *  steady price creep), so the chart shows no false highlight. */

const MAD_THRESHOLD = 3; // how many MADs above the median a point must sit to be an outlier
const RELATIVE_THRESHOLD = 0.5; // fallback when MAD is 0 (all values equal): fraction above the median
const MIN_POINTS = 4; // too few captures to reason about a "typical" price

function median(sortedAsc: number[]): number {
  const n = sortedAsc.length;
  if (n === 0) return 0;
  const mid = Math.floor(n / 2);
  return n % 2 === 0 ? (sortedAsc[mid - 1]! + sortedAsc[mid]!) / 2 : sortedAsc[mid]!;
}

/** Index of the strongest overpricing outlier in `values`, or `null` if none stands out. */
export function findPriceOutlierIndex(values: number[]): number | null {
  if (values.length < MIN_POINTS) return null;

  const med = median([...values].sort((a, b) => a - b));
  const mad = median(values.map((v) => Math.abs(v - med)).sort((a, b) => a - b));

  let bestIndex: number | null = null;
  let bestScore = 0;
  values.forEach((value, index) => {
    if (value <= med) return; // overpricing only
    const score = mad > 0 ? (value - med) / mad : (value - med) / med;
    const threshold = mad > 0 ? MAD_THRESHOLD : RELATIVE_THRESHOLD;
    if (score >= threshold && score > bestScore) {
      bestScore = score;
      bestIndex = index;
    }
  });

  return bestIndex;
}
