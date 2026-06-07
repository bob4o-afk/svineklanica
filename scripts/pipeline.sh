#!/usr/bin/env bash
# Свинекланица data refresh: scrape -> AI analyze -> ingest (EVALUATED ONLY) -> detect.
#
# Ordered + sequential so `ingest:run --require-verdict` only ever runs after the
# source's AI verdicts are written. Run on the prod VM hourly by cron (see DEPLOY.md)
# and reused by the deploy workflow, so the scheduled run and the on-deploy run are
# byte-for-byte the same path.
#
# The AGENTS_CAP / AGENTS_EVAL_CAP governors (set in .env.prod) bound each run to
# 100 concurrent Gemini calls and 100 total evaluations.
#
# Per-step failures are NON-FATAL (a flaky upstream must not break the whole run).
# Because ingest is gated on a verdict, a failed scrape/analyze simply means those
# records are NOT inserted — unevaluated data is dropped, never stored.
set -uo pipefail

# repo / deploy root (docker-compose.prod.yml + .env.prod live here), regardless of cwd.
cd "$(dirname "$0")/.."

export COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
export COMPOSE_ENV_FILES="${COMPOSE_ENV_FILES:-.env.prod}"

# Sources: env SCRAPE_SOURCES (space/comma list) -> else SCRAPE_SOURCES in .env.prod
# -> else "ted". Set it to "off"/"none" to disable the run.
SOURCES="${SCRAPE_SOURCES:-}"
if [ -z "$SOURCES" ]; then
  SOURCES="$(grep -E '^SCRAPE_SOURCES=' .env.prod 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '"' || true)"
fi
[ -z "$SOURCES" ] && SOURCES="ted"

if [ "$SOURCES" = "off" ] || [ "$SOURCES" = "none" ]; then
  echo "SCRAPE_SOURCES=$SOURCES — pipeline disabled, nothing to do."
  exit 0
fi

ts() { date -u +%FT%TZ; }

# One source's full chain: scrape -> analyze -> ingest. These three are sequential
# because each needs the previous one's output (you can't analyze before you scrape,
# or ingest a verdict that doesn't exist yet). `docker compose run` makes a uniquely
# named one-off container per call, so running this for several sources at once is safe.
run_source() {
  src="$1"
  echo "=== [$(ts)] start: $src ==="
  if ! docker compose --profile tools run --rm scraper uv run scrape --source "$src"; then
    echo "scrape $src failed — skipping its analyze/ingest (no records will be inserted)"
    return 0
  fi
  docker compose --profile tools run --rm ai uv run analyze --source "$src" \
    || echo "analyze $src failed (records without a verdict will be dropped, not inserted)"
  # --require-verdict: only AI-evaluated records land in the DB; the rest are dropped.
  docker compose run --rm --no-deps app php artisan ingest:run --source="$src" --require-verdict \
    || echo "ingest $src failed"
  echo "=== [$(ts)] done: $src ==="
}

# Fan out: every source's chain runs CONCURRENTLY, so a slow scraper for one source
# never holds up another source's data from being scraped, scored, and inserted.
pids=""
for src in $(echo "$SOURCES" | tr ',' ' '); do
  run_source "$src" &
  pids="$pids $!"
done
# Barrier: wait for every source to finish before recomputing detectors over the union.
for pid in $pids; do wait "$pid" || true; done

echo "=== [$(ts)] detect:run ==="
docker compose run --rm --no-deps app php artisan detect:run \
  || echo "detect:run failed (continuing)"

echo "=== [$(ts)] pipeline done ==="
