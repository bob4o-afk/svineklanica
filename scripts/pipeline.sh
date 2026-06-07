#!/usr/bin/env bash
# Свинекланица data refresh: scrape -> AI analyze -> ingest (EVALUATED ONLY) -> detect.
#
# Ordered per source (scrape -> analyze -> ingest, a data dependency), but sources run
# CONCURRENTLY so a slow source never blocks another's data. Run on the prod VM hourly
# by cron (see DEPLOY.md §6.1) and reused by the deploy workflow, so the scheduled run
# and the on-deploy run are the same path.
#
# AGENTS_CAP / AGENTS_EVAL_CAP (.env.prod) bound each run to 100 concurrent Gemini calls
# and 100 total evaluations. `ingest:run --require-verdict` only stores AI-evaluated
# records — anything without a verdict is dropped, never inserted.
#
# LOGS: every step's full stdout/stderr is captured to its own file under
#   logs/pipeline/<run>/   (on the VM, persistent — the one-off containers are gone after).
# A summary.log says which steps passed/failed, and logs/pipeline/latest points at the
# newest run. So an empty/failed refresh is fully debuggable after the fact.
set -uo pipefail

# repo / deploy root (docker-compose.prod.yml + .env.prod live here), regardless of cwd.
cd "$(dirname "$0")/.."

export COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
export COMPOSE_ENV_FILES="${COMPOSE_ENV_FILES:-.env.prod}"

# --- logging -----------------------------------------------------------------
ts() { date -u +%FT%TZ; }
RUN="$(date -u +%Y%m%dT%H%M%SZ)"
LOG_ROOT="${PIPELINE_LOG_DIR:-logs/pipeline}"
LOG_DIR="$LOG_ROOT/$RUN"
mkdir -p "$LOG_DIR"
SUMMARY="$LOG_DIR/summary.log"
PIPELINE_LOG="$LOG_DIR/pipeline.log"
ln -sfn "$RUN" "$LOG_ROOT/latest" 2>/dev/null || true   # convenience pointer; ignore if unsupported

# log() -> console + pipeline.log ; note() -> also the summary (the headline outcome lines).
log()  { echo "[$(ts)] $*" | tee -a "$PIPELINE_LOG"; }
note() { echo "[$(ts)] $*" | tee -a "$PIPELINE_LOG" "$SUMMARY"; }

# Run one step, capturing ALL its output to its own logfile; record pass/fail + show the
# tail on failure so the cron log alone tells you roughly what broke.
# Usage: run_step <logfile> <label> <cmd...>
run_step() {
  logfile="$1"; label="$2"; shift 2
  log "START $label  (-> $logfile)"
  if "$@" >"$logfile" 2>&1; then
    note "OK    $label"
    return 0
  fi
  rc=$?
  note "FAIL  $label (rc=$rc) -- see $logfile"
  { echo "----- last 25 lines of $logfile -----"; tail -n 25 "$logfile" 2>/dev/null; echo "-------------------------------------"; } | tee -a "$PIPELINE_LOG"
  return "$rc"
}

log "pipeline run $RUN starting; logs in $LOG_DIR"

# --- sources -----------------------------------------------------------------
SOURCES="${SCRAPE_SOURCES:-}"
if [ -z "$SOURCES" ]; then
  SOURCES="$(grep -E '^SCRAPE_SOURCES=' .env.prod 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '"' || true)"
fi
[ -z "$SOURCES" ] && SOURCES="ted"

if [ "$SOURCES" = "off" ] || [ "$SOURCES" = "none" ]; then
  note "SCRAPE_SOURCES=$SOURCES -- pipeline disabled, nothing to do."
  exit 0
fi
note "sources: $SOURCES"

# --- per-source chain (sources run concurrently) -----------------------------
run_source() {
  src="$1"
  log "=== source '$src' START ==="
  # --force: scrape the requested source even if <SRC>_ENABLED isn't set in env.
  # Without it the scraper logs "disabled" and writes 0 records (run.py) — which is
  # precisely how the DB ends up empty. We asked for this source explicitly, so run it.
  if ! run_step "$LOG_DIR/${src}.1-scrape.log" "scrape $src" \
        docker compose --profile tools run --rm scraper uv run scrape --source "$src" --force; then
    note "skip $src -- scrape failed, nothing scraped (no analyze/ingest for it)"
    return 0
  fi
  run_step "$LOG_DIR/${src}.2-analyze.log" "analyze $src" \
    docker compose --profile tools run --rm ai uv run analyze --source "$src" \
    || note "analyze $src failed -- its unevaluated records will be dropped at ingest"
  # --require-verdict: only AI-evaluated records land in the DB; the rest are dropped.
  run_step "$LOG_DIR/${src}.3-ingest.log" "ingest $src" \
    docker compose run --rm --no-deps app php artisan ingest:run --source="$src" --require-verdict \
    || note "ingest $src failed"
  log "=== source '$src' DONE ==="
}

pids=""
for src in $(echo "$SOURCES" | tr ',' ' '); do
  run_source "$src" &
  pids="$pids $!"
done
# Barrier: wait for every source before recomputing detectors over the union.
for pid in $pids; do wait "$pid" || true; done

# --- detectors over the freshly ingested union -------------------------------
run_step "$LOG_DIR/detect.log" "detect:run" \
  docker compose run --rm --no-deps app php artisan detect:run \
  || note "detect:run failed"

note "pipeline run $RUN complete -- full logs: $LOG_DIR"
