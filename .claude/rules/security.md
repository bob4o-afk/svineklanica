# Security rules — mandatory, not optional

> ⭐ = MUST. This whole file is effectively ⭐. We are a transparency/anti-corruption tool — being insecure would be both embarrassing and dangerous. **Public data only; authorized targets only** (see CLAUDE.md §2).

## 1. Every endpoint is guarded ⭐

- **No endpoint is open by accident.** Every route is wrapped by middleware and an explicit decision:
  - **Authenticated** routes: `auth:sanctum` (token auth, used by both web and mobile) + a **Policy** authorizing the specific action.
  - **Public read-only** routes (citizen browse): still pass through rate-limiting + abuse middleware, and expose only data meant to be public.
- Authorization lives in `Data::authorize()` / a `Policy`. **Never** inline `Gate::allows()` in a controller.
- There is a test proving an **unauthorized caller is rejected** for each guarded endpoint.

## 2. Rate limiting ⭐

- Global `throttle` middleware on the API, plus **tighter named limiters** on sensitive/expensive routes (login, search, scrape-trigger, exports).
- Limits are keyed by user when authenticated, by IP otherwise. Return `429` with `Retry-After`.
- Heavy operations are async jobs (see backend.md §3), so a request can't hold a connection open and exhaust the pool.

## 3. Abuse / bot / "hacker" detection → blacklist ⭐

- Maintain a **blacklist** (IP and/or token/fingerprint). Middleware rejects blacklisted callers early with `403`.
- **Auto-blacklist** on abuse signals: repeated `401/403`, rate-limit violations, scanner/SQLi/XSS/path-traversal signatures in inputs, absurd request rates, or known-bad bot signatures. Log every auto-ban (security channel) with the reason.
- **Bot/AI scraping of our own API** beyond what a human UI would do is throttled then blacklisted. (We scrape *public* sources politely; we don't let others hammer us.)
- Blacklist entries have a TTL/expiry and are reviewable — no permanent lockout by accident.

## 4. Allow/whitelist & CORS ⭐

- A **domain allow-list** is fine and encouraged: trusted origins (our web app, our mobile app's origin) are explicitly allowed.
- **CORS is locked down:** `allowed_origins` is an explicit list of our front-ends — **never `*`** on credentialed endpoints. Only the methods/headers we actually use are allowed.
- Outbound: when our scrapers fetch upstream, restrict to the **known public source domains** (an allow-list of data sources) — no fetching arbitrary user-supplied URLs (SSRF guard).

## 5. Input validation & sanitization ⭐

- **All input is validated AND sanitized** at the DTO boundary (Spatie Data) before it reaches any logic. Reject unexpected fields (no mass-assignment surprises).
- **Output is escaped to prevent script injection / XSS.** React escapes by default — **never** `dangerouslySetInnerHTML` with un-sanitized content; if HTML must be rendered, sanitize it (allow-list sanitizer) first.
- File uploads (if any): validate type/size, store outside the web root, never trust the original filename.

## 6. SQL injection — structurally impossible ⭐

- **Eloquent / query builder with bound parameters only.** **No string-concatenated SQL**, ever. If a raw expression is unavoidable, use parameter bindings (`?` / named) — never interpolate user input.

## 7. Passwords & secrets ⭐

- Passwords are **hashed + salted** with the framework hasher (bcrypt/argon2 via Laravel `Hash`) — **never** plaintext, never reversible encryption, never a fast/unsalted hash (`md5`/`sha1`).
- Secrets/API keys/DB creds live in **env** (not committed). `.env.example` documents the keys with empty values.
- Tokens (Sanctum) are stored securely client-side (secure storage on mobile, httpOnly/secure where applicable on web).

## 8. Transport & headers ⭐

- **HTTPS everywhere.** Security headers set (HSTS, `X-Content-Type-Options: nosniff`, a restrictive `Content-Security-Policy`, `X-Frame-Options`/frame-ancestors).
- Cookies (if used) are `Secure` + `HttpOnly` + `SameSite`.

## 9. Database safety ⭐

- Least-privilege DB user for the app. Migrations only — no manual prod edits.
- No sensitive data in logs (no passwords/tokens/PII in operational logs). Errors logged with context via the security channel, not leaked to the client (generic message + request id).

## 10. Honeypot / deception ⭐

- Expose **decoy routes** that no legitimate client ever calls and that are **not linked** anywhere (UI, sitemap, robots) — e.g. `/api/admin`, `/api/.env`, `/api/internal/db-dump`, `/wp-login.php`, `/.git/config`. Driven by `HONEYPOT_ROUTES` env.
- **A hit on a honeypot route is a near-certain bot/attacker signal** (especially a scanner walking all public URLs). On hit, the app:
  1. **fingerprints** the caller (IP, UA, headers) and **auto-adds them to the blacklist** (§3),
  2. responds with **believable but FAKE data from an isolated sandbox** (a separate fake dataset/connection — **never the real DB**) to waste their time and let us observe behavior,
  3. logs the full interaction to the `security` channel + monitoring for study.
- A **tarpit** complements it: callers spraying many `404`s / scanning rapidly get slowed then blacklisted.
- **Defensive only.** We observe attackers hitting *our* system — we never hack back, never probe them. The fake DB contains zero real or personal data.
- Implementation lives in the Identity/Security module (`HoneypotMiddleware` + a `HoneypotEvent` + the fake-data sandbox); the route list + toggles are env-driven (`HONEYPOT_ENABLED`, `HONEYPOT_SERVE_FAKE_DATA`).

## 11. Monitoring & observability ⭐

- **Uptime:** an external check hits `/_health`; alert on failure.
- **Metrics:** container + host metrics (cAdvisor + node-exporter → Prometheus → Grafana). When the API exposes `/metrics`, graph request rate, queue depth, **blacklist bans, and honeypot hits**.
- **Errors:** app errors (Laravel + React) go to Sentry.
- **Security signals are first-class:** every auto-ban and honeypot hit is logged to the `security` channel and should drive an alert — being attacked is an event we want to *see*, not discover later. (Ops wiring: `.claude/rules/devops.md` §7.)
