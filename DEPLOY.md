# DEPLOY — how this actually goes live

Two stages. **Stage 1 (VM + Docker Compose) is the goal for the hackathon.**
Stage 2 (Kubernetes) is there for later if it grows. Same Docker images for both.

```
push git tag vX.Y.Z
   └─ GitHub Actions (release.yml): run tests → build images → push to GHCR
        └─ VM pulls images → docker compose up -d → migrate → rolling swap
             └─ scrape → AI analyze (with caps) → ingest → detect → Caddy serves HTTPS → LIVE
```

GitHub Actions never *hosts* the app — it only builds + ships the images. The VM runs them.

**Four images** are built+pushed per tag: `svineklanitsa-api`, `svineklanitsa-web`,
and the on-demand Python tools `svineklanitsa-scraper` + `svineklanitsa-ai`. The two
tools are on the compose `tools` profile (not started by `up -d`) and share the
`ingest_data` volume with `app`/`queue`, so their NDJSON + verdicts are what
`php artisan ingest:run` reads.

==============================================================================
STAGE 1 — ONE VM + DOCKER COMPOSE  (do this)
==============================================================================

## 1. Get a VM
- **Hetzner Cloud** (~€4/mo, best value) / DigitalOcean / Vultr. Ubuntu 22.04+, 2 vCPU / 4 GB is plenty to start.
- Create an A record: `your-domain.bg → <VM public IP>` (and `www` if you want).
  No domain yet? Use a free **DuckDNS** subdomain, or front it with a **Cloudflare Tunnel** for a demo.

## 2. Install Docker on the VM
```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER   # re-login after this
```

## 3. Put the project + env on the VM
```bash
sudo mkdir -p /opt/liberhack && sudo chown $USER /opt/liberhack
cd /opt/liberhack
# copy the repo here (git clone, or scp these files):
#   docker-compose.prod.yml, Caddyfile, .env.prod
cp .env.prod.example .env.prod   # production template — then fill every <REPLACE_ME>:
```
Production config lives in **one file, `.env.prod`** (local dev uses `.env`).
In `.env.prod` set (all the `<REPLACE_ME>` slots in `.env.prod.example`):
- `APP_DOMAIN=your-domain.bg`  ← Caddy uses this to fetch a free Let's Encrypt cert
  (`APP_URL` / `FRONTEND_URL` / `CORS_ALLOWED_ORIGINS` / `SANCTUM_STATEFUL_DOMAINS` follow it)
- real `DB_PASSWORD`, real **mail** creds (see plan.txt §2A), `GRAFANA_ADMIN_PASSWORD`
- `REGISTRY_OWNER=<your github user/org>`, `IMAGE_TAG=v1.0.0`
- generate `APP_KEY`: `docker run --rm ghcr.io/<owner>/svineklanitsa-api:<tag> php artisan key:generate --show` → paste it in.
- (`APP_ENV=production` / `APP_DEBUG=false` are already set in the template.)
- The SSH deploy key is NOT in `.env.prod` — its private half goes in the GitHub secret `DEPLOY_SSH_KEY`.

## 4. Authenticate to GHCR + launch
```bash
echo $GHCR_TOKEN | docker login ghcr.io -u <github-user> --password-stdin   # only if images are private
docker compose --env-file .env.prod -f docker-compose.prod.yml pull
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan migrate --force
```
Visit `https://your-domain.bg` — Caddy has already issued the TLS cert. Done.

## 5. Auto-deploy on every push to `main` — ZERO-DOWNTIME
In the GitHub repo set these secrets (no `DEPLOY_ENABLED` flag anymore — every push to
`main` deploys):
- Secrets: `DEPLOY_SSH_HOST`, `DEPLOY_SSH_USER`, `DEPLOY_SSH_KEY` (a private key whose public half is in the VM's `~/.ssh/authorized_keys`), `DEPLOY_PATH=/opt/liberhack`.
Now any push to `main` → Actions runs tests, builds images tagged by the **commit SHA**,
scp's `docker-compose.prod.yml` + `Caddyfile` + `scripts/pipeline.sh` to the VM, SSHes in
and does a **rolling, zero-downtime swap**, migrates, refreshes data, and emails you.
(Images are pinned to the SHA — the VM can never silently run a stale `:latest`.)

How the zero-downtime deploy works (release.yml `deploy` job):
- The prod API image is **FrankenPHP** (`Dockerfile.prod`) — it serves requests
  concurrently and reloads gracefully, unlike `artisan serve`.
- `app` and `web` have **healthchecks** (compose); when available, the deploy uses
  the **`docker rollout`** CLI plugin (auto-installed on first deploy) which starts a
  NEW container, waits for its healthcheck to pass, then drains the OLD one. Caddy
  resolves upstreams **dynamically** (root `Caddyfile`) and load-balances across the
  overlap, so no request is dropped. If the plugin can't be made available on the host
  (e.g. docker runs via `sudo` with a different config dir), the deploy **falls back to
  a plain `compose up -d` recreate** — a brief restart blip instead of a failed release.
- Migrations run with the new image BEFORE the swap — **keep them backward-compatible**
  (expand/contract) so the old container keeps working during the overlap.
- `db`/`redis`/`proxy` are never stopped; only the app/web containers roll.
- Prereqs on the VM: `curl` (for the plugin install) + Docker Compose v2.24+
  (for `COMPOSE_ENV_FILES`). `docker rollout` self-installs to
  `${DOCKER_CONFIG:-~/.docker}/cli-plugins` — the dir the host's `docker` actually
  reads (so it's found even when docker is invoked via `sudo`).

## 6. Fresh data — scrape → AI analyze → ingest → flags → detect

The whole refresh is one script — **`scripts/pipeline.sh`** — run two ways from the
SAME code: at the end of every deploy (§5), and **hourly via cron** (§6.1). For each
source it runs, in order: the Python **scraper** → the AI **analyzer** → `ingest:run
--require-verdict` (the evaluated tenders) → `analyze:ingest` (turns the AI verdicts into
**Flags on those tenders**, so they appear in the citizen flag-posts feed/map). After all
sources finish it runs `detect:run` once (the deterministic detector flags) over the union.

**Only AI-evaluated records are stored.** `--require-verdict` gates ingest on the
analyzer's verdict sidecar: a record with no verdict — because the analyzer errored,
or the source's analyze step failed — is **dropped, never inserted** (CLAUDE.md: no
source/eval → not in the DB). The analyzer also skips a record that throws instead of
killing the batch, so one bad record can't poison the run. All steps are **non-fatal** —
a flaky upstream logs a warning but never fails an otherwise-good deploy. (Ingest-first:
this runs at deploy/cron time, never live during a demo.)

Control it with repo **variables** (Settings → Secrets and variables → Actions → Variables):
- **`SCRAPE_SOURCES`** — space/comma list of source ids to refresh on each deploy.
  Defaults to `ted` when unset. Set it to `off` (or `none`) to skip scraping entirely.
  Example: `SCRAPE_SOURCES = ted, nhif, mvr`.

The analyzer's cost/concurrency caps come from **`.env.prod`** on the VM (used by the
`ai` service's `env_file`), so the auto-run self-limits:
- **`GOOGLE_API_KEY`** — Gemini key; without it the analyzer runs deterministic-only.
- **`AGENTS_CAP`** — max **concurrent** agent calls in flight (async ceiling; default 100).
- **`AGENTS_EVAL_CAP`** — max **total LLM CALLS** per run (NOT records); `0` = unlimited
  (default 100). Each record fans out to several agents, so 100 calls ≈ ~25 fully-LLM-scored
  records; the rest "degrade to deterministic-only" (logged) and are **still ingested**
  (a deterministic verdict still counts). Raise it (e.g. 300) for more LLM coverage per run.
- **`SCRAPE_SOURCES`** — (optional) space/comma list of sources for the cron run; defaults
  to `ted`, `off`/`none` disables. The deploy reads it from the repo variable instead.
- **`PIPELINE_MIN_SCORE`** — (optional, 0–100, default `0`) only AI verdicts at/above this score
  become flag posts (passed to `analyze:ingest --min-score`). `0` shows every evaluated record;
  raise it (e.g. `40`) so the feed surfaces only the suspicious cases.

Run the whole thing by hand on the VM exactly as cron/deploy do:
```bash
cd /opt/liberhack && bash scripts/pipeline.sh           # all sources in SCRAPE_SOURCES
# …or one stage at a time:
export COMPOSE_FILE=docker-compose.prod.yml COMPOSE_ENV_FILES=.env.prod
# NOTE the --force: a source only scrapes if <SRC>_ENABLED=true in env OR --force is passed.
# Without it the scraper logs "disabled" and writes 0 records → empty DB. pipeline.sh passes
# --force for you; pass it yourself when running scrape by hand.
docker compose --profile tools run --rm scraper uv run scrape --source ted --force
docker compose --profile tools run --rm ai uv run analyze --source ted
docker compose run --rm --no-deps app php artisan ingest:run --source=ted --require-verdict
docker compose run --rm --no-deps app php artisan analyze:ingest --source=ted   # AI verdicts → flag posts
docker compose run --rm --no-deps app php artisan detect:run                    # deterministic flags
```

### 6.1 Hourly refresh via cron — auto-installed by the deploy
The scrape/AI/ingest tools are one-shot batch jobs (compose profile `tools`), not daemons,
so they're driven by the host scheduler. **You don't add this by hand** — the deploy
(`release.yml`) **idempotently installs the crontab line on every release**, so the hourly
refresh is guaranteed on any VM the deploy has touched (no manual `crontab -e`). The line it
ensures (at `:05`, logging to the deploy dir so it's writable by the deploy user):
```bash
5 * * * * cd $DEPLOY_PATH && /usr/bin/bash scripts/pipeline.sh >> $DEPLOY_PATH/logs/pipeline-cron.log 2>&1
```
The install strips any prior `scripts/pipeline.sh` cron line before re-adding, so re-deploys
never duplicate it. Each run is bounded by `AGENTS_CAP` / `AGENTS_EVAL_CAP` (100/100), so an
hourly cadence stays within Gemini quota. Confirm it's installed/firing on the VM with:
```bash
crontab -l | grep pipeline.sh                  # the line is present
tail -f $DEPLOY_PATH/logs/pipeline-cron.log     # cron stdout, each hour
```
(`scripts/pipeline.sh` is scp'd to the VM by the deploy, so it's always present + current.)

Sources run **concurrently** (one source's slow scrape never blocks another's data), but
the three stages *within* a source stay ordered (scrape → analyze → ingest — a data
dependency). Detectors run once at the end over the union.

### 6.2 Logs — where to look when a run is empty or fails
Every pipeline run writes a folder of per-step logs **on the VM** (so the throwaway
`docker compose run` containers are debuggable after they exit):
```
<DEPLOY_PATH>/logs/pipeline/<UTC-timestamp>/
  summary.log              # one line per step: OK / FAIL  (read this first)
  pipeline.log             # the run timeline + tail of any failed step
  <source>.1-scrape.log    # full scraper output ("ingested N" — N=0 means nothing scraped)
  <source>.2-analyze.log   # full analyzer output ("analyzed N, flagged M, written -> …")
  <source>.3-ingest.log    # ingest table: Read / Written / Skipped + every skip reason
  <source>.4-analyze-ingest.log  # AI verdicts → flags (the flag-posts feed content)
  detect.log               # deterministic detector recompute
logs/pipeline/latest       # → symlink to the newest run
```
Quick triage:
```bash
cat  /opt/liberhack/logs/pipeline/latest/summary.log
tail -n 50 /opt/liberhack/logs/pipeline/latest/ted.3-ingest.log
```
App-level detail (ingest counts, errors, queued-mail failures) also goes to Laravel's log
inside the container: `docker compose exec app tail -n 100 storage/logs/laravel.log`.

### 6.3 Troubleshooting "deploy OK but the DB is empty"
Walk the stages in order — the first one that reports `0` is the culprit:
1. **scrape `ingested 0`** → the source was skipped because it's not enabled and `--force`
   wasn't passed (`<source>.1-scrape.log` will say `disabled`). `pipeline.sh` passes
   `--force`; if you scraped by hand, add it. (Or set `<SRC>_ENABLED=true` in `.env.prod`.)
   A real `0` with `--force` means the upstream genuinely returned nothing — check the URL/network.
2. **analyze ran but ingest `Written: 0`** → look at `<source>.3-ingest.log` skip reasons.
   `not evaluated — no AI verdict` means the verdict sidecar didn't cover those records
   (analyze failed or wrote nothing); `--require-verdict` then drops them by design.
3. **`unauthorized` / image `not found` on pull** → GHCR auth / stale image (see §4 + the
   `docker login ghcr.io` step). The VM never got the new images, so the tools ran old code.
4. Confirm what landed: `docker compose exec app php artisan tinker --execute='dump(\Modules\Procurement\Models\Tender::count(), \Modules\Detection\Models\Flag::count());'`

==============================================================================
MONITORING  (bring up next to the app)
==============================================================================
```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml -f docker-compose.monitoring.yml up -d
```
- **Grafana** :3000 (dashboards; login `GRAFANA_ADMIN_*` from .env.prod) ← Prometheus ← cAdvisor (containers) + node-exporter (host).
- **Uptime Kuma** :3001 — add a monitor for `https://your-domain.bg/_health`, wire Discord/email alerts.
- **App errors** → Sentry (set `SENTRY_LARAVEL_DSN` + `VITE_SENTRY_DSN`) — SaaS, not in compose.
- **Security events** (honeypot hits, blacklist bans) are logged to the `security` channel and surface in Grafana/alerts.
Lock these dashboards behind auth / the firewall — don't expose :3000/:3001 publicly without a password.

==============================================================================
HONEYPOT / DECEPTION  (app code, spec in .claude/rules/security.md §10)
==============================================================================
Decoy routes (`HONEYPOT_ROUTES` in .env) that no real client ever calls and that
aren't linked anywhere. A hit ⇒ almost certainly a bot/scanner ⇒ the app
fingerprints them, **auto-adds them to the blacklist**, serves believable **fake**
data from an isolated sandbox (never the real DB), and logs everything for study.
This is implemented in the Identity/Security module when we write code.

==============================================================================
STAGE 2 — KUBERNETES  (LATER, only if it needs to scale)
==============================================================================
Manifests live in `deploy/k8s/` with their own README. You do NOT need this to
launch. Roughly: managed cluster or k3s → ingress-nginx + cert-manager (TLS) →
`kubectl apply -k deploy/k8s` → migrate. Same images, more orchestration.
See `deploy/k8s/README.md`.
