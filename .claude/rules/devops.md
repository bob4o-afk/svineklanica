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

## 1.1 Local dev = `https://localhost` via Caddy ⭐

**The one canonical local URL is `https://localhost`. Everyone uses it. Don't develop against `http://localhost:5173` unless you've deliberately opted into direct mode (last section below).** Using the canonical URL is what gives us a real secure context (so the PWA service worker, MSW, and `wss` HMR all behave exactly like prod) and what makes the `/api` proxy, security headers, and TLS match what ships.

### What Caddy is (and why)

**Caddy** is the `proxy` service in `docker-compose.yml` (`caddy:2-alpine`). It's a reverse proxy that sits in front of everything on ports **80/443** and:
- **Terminates TLS** — with `APP_DOMAIN=localhost` it mints a **locally-trusted internal certificate** from its own built-in CA, so you get real `https://` in dev. (With a real `APP_DOMAIN` in prod it auto-fetches a free Let's Encrypt cert instead — same config, see `Caddyfile` + DEPLOY.md.)
- **Routes by path** (`Caddyfile`): `/api/*`, `/sanctum/*`, `/_health` → the Laravel `app` container (`:8000`); **everything else** → the Vite `web` dev server (`:5173`). So the browser only ever talks to `:443`; Caddy fans out behind it.
- **Sets the security headers** (HSTS, `X-Content-Type-Options`, `X-Frame-Options`, …) from `security.md §8`, so dev mirrors prod.

This is why you never point the browser at `:8000` or `:5173` directly — Caddy is the single front door for both the API and the app.

### First-time setup (once per machine)

```bash
# 1. Env files (root = Laravel + compose vars; web = Vite vars)
cp .env.example .env
cp apps/web/.env.example apps/web/.env

# 2. Build images, start the stack, install deps, migrate (+ seed)
make build && make up && make install && make migrate && make seed

# 3. TRUST CADDY'S LOCAL ROOT CA  — the step everyone forgets.
#    Without it the browser shows a cert warning (NET::ERR_CERT_AUTHORITY_INVALID)
#    and the PWA/secure-context features silently misbehave.
#    Caddy generates the CA inside its data volume on first run; copy it out:
docker compose cp proxy:/data/caddy/pki/authorities/local/root.crt ./caddy-local-root.crt
```

Then import `caddy-local-root.crt` into the OS **trusted root** store:

- **Windows (PowerShell, run as Administrator):**
  ```powershell
  Import-Certificate -FilePath .\caddy-local-root.crt -CertStoreLocation Cert:\CurrentUser\Root
  ```
  (or double-click the `.crt` → *Install Certificate* → *Current User* → *Place all certificates in the following store* → **Trusted Root Certification Authorities**.)
- **macOS:** `sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ./caddy-local-root.crt`
- **Linux:** copy to `/usr/local/share/ca-certificates/` and run `sudo update-ca-certificates`.

**Restart the browser** after importing, then open **`https://localhost`**. No warning = trust worked. (Captured dev emails: Mailpit at `http://localhost:8025`.)

### HMR must match the entry point ⭐

The two HMR modes are **mutually exclusive — pick one per running dev server** (`apps/web/vite.config.ts server.hmr`, toggled by the `VITE_HMR_DIRECT` env on the `web` service in `docker-compose.yml`):

| You browse | `VITE_HMR_DIRECT` | HMR connects to |
|---|---|---|
| **`https://localhost`** (canonical — the default) | **`"false"`** | `wss://localhost:443` (through Caddy) ✅ |
| `http://localhost:5173` (direct Vite, opt-in) | `"true"` | `ws://localhost:5173` |

**The repo default is `"false"` — leave it.** If you set it to `"true"` *and* browse `https://localhost`, the HMR client tries to reach `localhost:5173`, the https page forces TLS onto that plaintext port, and you get **`GET https://localhost:5173/ net::ERR_SSL_PROTOCOL_ERROR`**. After changing the var, recreate the container: `docker compose up -d web`.

### Quick troubleshooting

- **`ERR_SSL_PROTOCOL_ERROR` on `localhost:5173`** → HMR mode mismatch. Set `VITE_HMR_DIRECT="false"` and `docker compose up -d web` (see table above).
- **Cert warning / `ERR_CERT_AUTHORITY_INVALID`** → the trust step wasn't done (or the `caddy_data` volume was recreated, minting a new CA — re-export and re-import).
- **`/api/*` 404 or won't load** → you're hitting Vite directly, or a stale PWA service worker is controlling the page. Use `https://localhost`; hard-reload (Ctrl+Shift+R) to drop a stale SW.

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
