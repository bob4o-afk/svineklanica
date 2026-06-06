<#
.SYNOPSIS
  End-to-end Свинекланица pipeline test: scrape REAL public data -> ingest into
  the DB -> run the AI corruption analyzer -> report.

.DESCRIPTION
  Drives all three lanes through Docker (no host uv/make needed):

    1. apps/scraper (Python)  uv run scrape   -> storage/ingest/normalized/<src>.ndjson
    2. Laravel app            ingest:run      -> tenders/payments/... rows in Postgres
    3. apps/ai (Python)       uv run analyze  -> storage/ingest/verdicts/<src>.ndjson
       (Gemini multi-agent: sphere -> category -> red-flag agents -> 0-100 score + level + flags)

  The AI step needs a Gemini key. The env var is GOOGLE_API_KEY (NOT GEMINI_API_KEY),
  and it lives in apps/ai/.env. Without it the analyzer still runs but degrades to
  deterministic-only (LLM agents return neutral) - see -NoLlm.

.PARAMETER Source
  Scraper/analyzer source id (default: ted). See `uv run scrape --list`.

.PARAMETER Limit
  Max records to scrape + analyze (default: 50). Keep small for a quick test.

.PARAMETER NoLlm
  Skip Gemini entirely (deterministic-only). Use to test the chain with no key/tokens.

.PARAMETER SkipScrape
  Reuse the existing normalized NDJSON instead of hitting the network again.

.EXAMPLE
  .\scripts\pipeline.ps1 -Source ted -Limit 50
.EXAMPLE
  .\scripts\pipeline.ps1 -Source ted -NoLlm        # no key required
#>
[CmdletBinding()]
param(
    [string]$Source = "ted",
    [int]$Limit = 50,
    [switch]$NoLlm,
    [switch]$SkipScrape
)

$ErrorActionPreference = "Stop"
$repo = Split-Path -Parent $PSScriptRoot
Set-Location $repo

function Step($n, $msg) { Write-Host "`n=== [$n] $msg ===" -ForegroundColor Cyan }
function Warn($msg)     { Write-Host "WARN: $msg" -ForegroundColor Yellow }

# --- 0. Preconditions ---------------------------------------------------------
Step 0 "Checking prerequisites"

$aiEnv = Join-Path $repo "apps/ai/.env"
$aiEnvExample = Join-Path $repo "apps/ai/.env.example"
if (-not (Test-Path $aiEnv)) {
    Copy-Item $aiEnvExample $aiEnv
    Warn "Created apps/ai/.env from the example. Put your Gemini key in it:"
    Warn "    GOOGLE_API_KEY=<your-key-from https://aistudio.google.com/apikey>"
}

$hasKey = (Select-String -Path $aiEnv -Pattern '^\s*GOOGLE_API_KEY=\S' -Quiet)
if (-not $hasKey -and -not $NoLlm) {
    Warn "No GOOGLE_API_KEY set in apps/ai/.env -> the AI agents will run NEUTRAL"
    Warn "(deterministic features still score). Add the key, or re-run with -NoLlm."
}

# --- 1. Build the Python images (scraper + ai) --------------------------------
Step 1 "Building scraper + ai images"
docker compose --profile scrape --profile ai build scraper ai

# --- 2. Scrape REAL data ------------------------------------------------------
if ($SkipScrape) {
    Step 2 "Skipping scrape (reusing storage/ingest/normalized/$Source.ndjson)"
} else {
    Step 2 "Scraping '$Source' (limit $Limit) -> normalized NDJSON"
    # --force runs even if <SRC>_ENABLED is not set in apps/scraper/.env
    docker compose --profile scrape run --rm scraper `
        uv run scrape --source $Source --limit $Limit --force
}

$normalized = Join-Path $repo "storage/ingest/normalized/$Source.ndjson"
if (-not (Test-Path $normalized)) {
    throw "No normalized file at $normalized - scrape produced nothing. Try another -Source or check apps/scraper logs."
}
$lines = (Get-Content $normalized | Measure-Object -Line).Lines
Write-Host "  normalized records: $lines" -ForegroundColor Green

# --- 3. Ingest into the database ---------------------------------------------
Step 3 "Ingesting '$Source' into Postgres (idempotent upsert)"
docker compose run --rm app php artisan ingest:run --source=$Source

# --- 4. Run the AI corruption analyzer ---------------------------------------
Step 4 "Analyzing '$Source' with the AI flows -> verdicts NDJSON"
$analyzeArgs = @("uv", "run", "analyze", "--source", $Source, "--limit", $Limit)
if ($NoLlm) { $analyzeArgs += "--no-llm" }
docker compose --profile ai run --rm ai @analyzeArgs

# --- 5. Report ----------------------------------------------------------------
Step 5 "Results"

Write-Host "`n  DB row counts:" -ForegroundColor Green
docker compose exec -T db psql -U liberhack -d liberhack -c `
  "SELECT 'tenders' t, count(*) FROM tenders UNION ALL SELECT 'payments', count(*) FROM payments UNION ALL SELECT 'authorities', count(*) FROM contracting_authorities UNION ALL SELECT 'companies', count(*) FROM companies UNION ALL SELECT 'ingest_records', count(*) FROM ingest_records;"

$verdicts = Join-Path $repo "storage/ingest/verdicts/$Source.ndjson"
if (Test-Path $verdicts) {
    $vlines = (Get-Content $verdicts | Measure-Object -Line).Lines
    Write-Host "`n  AI verdicts written: $vlines  ->  $verdicts" -ForegroundColor Green
    Write-Host "  Sample verdict (first record):" -ForegroundColor Green
    Get-Content $verdicts -First 1 | ForEach-Object {
        try { ($_ | ConvertFrom-Json | Select-Object natural_key, sphere, category, flow_key, corruption_score, level | Format-List | Out-String).Trim() }
        catch { $_.Substring(0, [Math]::Min(400, $_.Length)) }
    }
} else {
    Warn "No verdicts file at $verdicts"
}

Write-Host "`nDone." -ForegroundColor Cyan
Write-Host "NOTE: AI verdicts land in NDJSON files (storage/ingest/verdicts/), not yet in the DB." -ForegroundColor DarkYellow
Write-Host "      The DB 'flags' table is populated by the Laravel detectors instead:" -ForegroundColor DarkYellow
Write-Host "      docker compose run --rm app php artisan detect:run" -ForegroundColor DarkYellow
