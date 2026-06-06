# Admin Phase 4 — manual verification checklist

> What to click through later to catch anything the automated gates (typecheck · lint · 46 vitest ·
> build) and the both-mode Playwright pass didn't. Built **on MSW** (no real backend yet). Tick each;
> if one fails, note the screen + what you saw so we can fix it.

**Run:** `corepack pnpm -C apps/web dev` → open the printed `localhost` URL.
**Demo editor creds:** `admin@example.com` / `parola`.
**Reach admin:** footer „Админ" link, or go straight to `/admin/login`.
> If `/api/*` 404s after a code edit: stale service worker → DevTools ▸ Application ▸ unregister, hard-reload.

---

## A. Auth & access control
- [ ] Logged out, visit `/admin` → redirected to `/admin/login` (same for `/admin/pending`, `/admin/sources`, `/admin/review/flag-1`).
- [ ] Login with correct creds → lands on `/admin` (dashboard).
- [ ] Login with a wrong password → red inline error („Грешен имейл или парола."), stays on login, no crash.
- [ ] Already logged in, visit `/admin/login` → bounced to `/admin`.
- [ ] „Изход" → back to `/admin/login`; revisiting `/admin` now redirects to login again.
- [ ] Email + password fields have visible labels and are `required`; password is masked.

## B. Dashboard & shell
- [ ] Two cards with counts that match reality (start: **6** pending, **4** sources).
- [ ] „Отвори" buttons go to the queue / sources; the tabs (Табло · За преглед · Източници) switch screens.
- [ ] Opening a review keeps the „За преглед" tab highlighted.
- [ ] „Влязъл като: Редактор" shows the logged-in editor.

## C. Review queue + ReviewPanel
- [ ] Queue shows **only pending** flags; columns render: date, type badge, severity chip, subject, source-count.
- [ ] Clicking a row opens that flag's review panel.
- [ ] Each source link opens in a new tab and shows the host; a missing/invalid URL shows a warning, never a silent link.
- [ ] Title + explanation are prefilled and editable; edits stick while you're on the page.
- [ ] Punk-tag chips toggle on/off; any tags the flag already had start selected.
- [ ] **Approve** → success toast, returns to queue, the flag is gone, pending count drops by 1.
- [ ] After approving, the flag shows on the **public** side (open its post via in-app navigation) **with your edited title/explanation + the tags**. ⚠️ navigate client-side — a hard reload resets the mock (see §G).
- [ ] **Reject** → toast, leaves the queue, and does **not** appear in the public feed.
- [ ] Visiting a bogus `/admin/review/does-not-exist` → „Сигналът не е намерен" (no crash).

## D. Sources CRUD
- [ ] Grid lists the seeded sources (TED, data.egov, АОП/РОП, СЕБРА); the active/inactive chip matches each row.
- [ ] „Нов източник" opens the dialog; „Запази" stays disabled until key + label + a valid `http(s)` base URL are filled.
- [ ] Creating a source → it appears in the grid + success toast.
- [ ] Pencil (edit) → dialog pre-filled with that source; saving updates the row.
- [ ] Clicking the active/inactive chip flips the source's enabled state.
- [ ] Trash → confirm dialog naming the source; confirming removes it (+toast), cancelling keeps it.

## E. Look & feel — BOTH light and dark
- [ ] Every admin screen is legible in **light AND dark** (toggle in the header); nothing washed-out or invisible.
- [ ] Punk-tag chips + severity/type/sector badges read correctly in both modes.
- [ ] At phone width: the tabs scroll, and the forms/grids/dialog stay usable (no overflow off-screen).

## F. i18n / content
- [ ] No English or raw key strings leak anywhere in the admin UI — all Bulgarian.
- [ ] Tag labels read: **Крадене на пари / Кофти сделки / Шуши-муши**.

## G. Known caveats — EXPECTED, not bugs (don't "fix" these on MSW)
- [ ] Approvals / source edits **reset on a hard page reload** (the mock store is in-memory) — and a reload logs the editor out. For the live „watch it publish" demo, click through the SPA; don't F5. Disappears once the real backend is wired.
- [ ] `GET /api/admin/me` returns **401 on every public page** (logged in the console) — that's the normal "not an admin" probe, harmless in dev.
- [ ] The map is a **d3-geo choropleth**, not Mapbox — a deliberate offline/demo-safe choice.

## H. Re-run the gates before the demo
- [x] `corepack pnpm -C apps/web typecheck` · `… lint` · `… exec vitest run` (54 pass) · `… build` — all green. _(Session 5, after viz QA + fixes.)_

## I. When the BACKEND lands (seam reconciliation — cross-lane, see `API_SEAM.md`)
- [ ] Backend ships `/api/admin/*` exactly per `API_SEAM.md` (csrf-cookie, login/logout/me, flag-posts queue+detail+approve+reject, sources CRUD) and **enforces the Sanctum session + an editor policy server-side** — the client `ProtectedRoute` is UX only.
- [ ] Approve persists `tags` (the enum keys `theft|dodgy_deal|shushi_mushi`, not BG text) and sets `published_at`; the public feed/detail must exclude anything not `approved`.
- [ ] `composer sync:api-types` → regenerate `generated.d.ts`; reconcile vs `contract.ts` (esp. `AdminUser`, `Source`, `ReviewDecision`, `PunkTag`, `FlagPost.tags`), then flip `types/api.ts` and set `VITE_ENABLE_MOCKS=false` + `VITE_API_URL`.
- [ ] Verify CORS allow-list, cookie flags, and CSP `connect-src` cover the cookie auth + the `/sanctum/csrf-cookie` origin.
- [ ] (Optional) silence the `me` 401 prod-log noise: add a silent-probe opt-out so the interceptor doesn't ship it.
