# DevOps rules — Docker build + GitHub Actions CI

> ⭐ = MUST. Everything builds and runs in **Docker**; CI lives in **GitHub Actions**.

## 1. Docker is the build/runtime ⭐

- The app builds and runs entirely in Docker — no "works on my machine". Local dev and CI use the same images.
- **`docker-compose.yml`** for local dev brings up the full stack:
  - `app` — PHP/Laravel (API) container.
  - `web` — Node container building/serving the React + Tailwind frontend.
  - `db` — PostgreSQL.
  - `redis` — queue + cache backend.
  - `queue` — a worker container running `php artisan queue:work` (so async jobs from backend.md §3 actually run).
  - (optional) `scheduler` for periodic scrapes.
- **Multi-stage Dockerfiles:** a build stage (composer install / `vite build`) and a slim runtime stage. No dev dependencies or secrets baked into the runtime image.
- Config comes from **env** at runtime, never hardcoded. Ship a `.env.example`.
- `make`/compose targets: `up`, `down`, `build`, `test`, `migrate`, `seed` — one command each.

## 2. GitHub Actions — tests on every push ⭐

Workflow `.github/workflows/ci.yml`, triggered on **push** and **pull_request**:

1. Spin up Postgres + Redis service containers (or build the compose stack).
2. **Backend:** install, run migrations, run the **Pest** suite (real Postgres).
3. **Frontend:** install, typecheck, lint, run the **Vitest** suite + build (proves web + mobile build).
4. A failing test **fails the pipeline** and blocks merge. No green-washing skips.

## 3. GitHub Actions — version tag → build + email notifier ⭐

Workflow `.github/workflows/release.yml`, triggered on **tag push** (`v*` — semantic versioning):

1. Run the full test suite first (don't notify about a broken release).
2. Build the production Docker image(s) / artifacts for the tagged version.
3. **Run the notifier: send an email to the user** announcing the new version (tag name, changelog/commits since last tag, build status, links).
   - The email send itself goes **through the app's queued mail Job** (consistent with backend.md §3 — email is async) or a dedicated notifier step; either way it's a real send, logged.
   - SMTP / mail-provider credentials come from **GitHub Actions secrets**, never committed. Recipient is configurable (secret/var).
4. On failure, the workflow surfaces it (and may notify of a failed release too).

## 4. Branch & version flow ⭐

- Feature branches → PR (CI must be green) → merge.
- Releases are cut by pushing a **semver tag** (`v1.0.0`), which is what triggers the notifier in §3.
- Single-line conventional commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`, `ci:`), module scope in parens where it helps (`feat(detection): …`).

## 5. Secrets & safety ⭐

- All credentials (DB, mail, API keys, tokens) live in **GitHub Actions secrets** / runtime env — never in the repo, never in an image layer, never echoed in logs.
- CI does not run untrusted code from forks with access to secrets (use the standard `pull_request` vs `pull_request_target` care).

## 6. Deploy: one VM + Docker Compose (Stage 1) ⭐

- **CI builds & ships; the VM runs.** GitHub Actions isn't the host — `release.yml` builds images and **pushes them to GHCR** on a `vX.Y.Z` tag. The server pulls and runs them.
- **Production runtime = `docker-compose.prod.yml`** on a single VM: it **pulls** the published images (no build on the box), and **Caddy** terminates TLS (free auto-renewing Let's Encrypt when `APP_DOMAIN` is a real domain).
- Optional auto-deploy: with repo variable `DEPLOY_ENABLED=true` + the `DEPLOY_SSH_*` secrets, `release.yml` SSHes into the VM to `pull` + `up -d` + `migrate`.
- Secrets/`.env` live **on the VM**, never in the repo or an image layer. db/redis are not exposed to the host in prod; prefer a managed DB as you grow.
- The full runbook is `/DEPLOY.md`.

## 7. Monitoring & observability ⭐

- Optional overlay `docker-compose.monitoring.yml`: **Prometheus** (scrapes **cAdvisor** + **node-exporter**), **Grafana** (dashboards), **Uptime Kuma** (hits `/_health`).
- **Sentry** for app errors (Laravel + React). **Security events** (honeypot hits, blacklist bans — security.md §10–§11) are logged to the `security` channel and must drive alerts.
- Don't expose Grafana/Kuma publicly without auth + firewall.

## 8. Kubernetes (Stage 2 — later, only at scale)

- **Not needed for the hackathon or first launch.** Reach for it only for multi-node scale / self-healing / zero-downtime rollouts.
- Manifests in `deploy/k8s/` (one Deployment/Service per component, HPA on the API, CronJob scheduler, Ingress + cert-manager for TLS). Same images as the VM deploy. See `deploy/k8s/README.md`.
