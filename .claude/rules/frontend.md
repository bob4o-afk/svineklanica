# Frontend rules (React + TypeScript + Tailwind) — standards

> ⭐ = MUST follow, no exceptions. The rest are strong defaults.
> The product must be **usable by a non-technical Bulgarian citizen** (20% of the score) — clarity beats cleverness.
> **Abstraction & reuse are the goal.** A component or helper is written once and reused; no copy-paste.

## 0. One web app that IS the mobile experience ⭐

- **There is no separate native mobile application.** "Mobile" = the **same web app**, built **mobile-first and fully responsive**, and installable as a **PWA** (manifest + service worker) so users can add it to their home screen.
- **One React + TypeScript + Tailwind codebase** serves every form factor — phone → tablet → desktop. **No desktop-only views**; every screen works from a phone width up.
- That is what "web + mobile versions" means for us: **one codebase, two form factors, zero duplicated platform code.** Reach many users without maintaining a second app.

## 1. Styling: MUI components + Tailwind utilities + tokens ⭐

- **Component library is MUI** (Material UI), including **MUI X** (DataGrid, charts, date pickers) — Premium/Pro features where we have a license key, Community otherwise. **Tailwind** sits on top for layout/spacing/utility classes.
- To stop MUI's baseline and Tailwind's reset from fighting, **Tailwind Preflight is disabled** (`corePlugins.preflight = false`) and Tailwind runs `important: '#root'`. MUI owns the CSS baseline; Tailwind decorates.
- MUI styling goes through the **theme + `sx`**; Tailwind via `className`. Either way: **no hardcoded colors / magic values.** Colors, spacing, fonts, radii live in the **MUI theme + `tailwind.config` tokens**. Never `#ff0000`, `rgb()`, `hsl()`, or `bg-[#fff]` in a component. New color → add to the theme/tokens first.
- **MUI X Premium needs a license key** — set `VITE_MUI_X_LICENSE_KEY` and call `LicenseInfo.setLicenseKey(...)` once at app bootstrap (where to get it: see `plan.txt`). Community components work without a key.
- **Punk look note:** the aesthetic is loud (high-contrast, zine, redacted-black, alarm-red). All the more reason to define the palette **once** in the theme/tokens and reuse.

## 2. Reusable `App*` components ⭐

- Every shared/reusable component is named `App<X>` (`AppButton`, `AppModal`, `AppTable`, `AppCard`, `AppFlagBadge`…) and lives in `apps/web/src/components/`.
- **One component per file. File name === default export name.** Export a `<Component>Props` interface. `forwardRef` for components wrapping a DOM element.
- **Always reach for an `App*` wrapper** before raw markup/3rd-party widgets. If a pattern appears twice, extract an `App*` component. Pages are orchestrators — no big inline JSX blocks with their own state/effects.

## 3. No hardcoded strings ⭐

- **Every user-facing string goes through i18n `t('key')`.** No string literals in JSX/props that a user can read. UI is **Bulgarian-first**; keys live in one catalog shared by web + mobile.
- **Translation values are sentence-case content; display casing is CSS** (`uppercase`/`capitalize` via a Tailwind class) — store `'Преглед'`, not `'ПРЕГЛЕД'`.
- Scraped **data values** (institution names, item text) are content, rendered as-is — not translated.

## 4. HTTP + logging discipline ⭐

- **All network calls go through one `http` wrapper** (`apps/web/src/lib/http.ts`) — the same client used by web and mobile. **No raw `fetch()`** anywhere else; any `axios` stays inside that wrapper. The wrapper attaches the auth token, base URL, and error handling in one place.
- **No `console.*`** in app code — go through one `logger` module (`logger.error` ships to the backend; render logs are dev-only).
- Each page/route component logs its mount via a `useRenderLog('Path/Name')` hook at the top of its body.

## 5. TypeScript discipline ⭐

- **No `undefined` literals.** Omit the key (conditional spread) or use `null`. Never `T | undefined` in a return type, never `foo: undefined`, never `?? undefined`. `foo?: T` in an interface is fine.
- **No `as any` / `as never`.** Cast the untyped value to its real type at the narrowest boundary instead.
- **Use the generated API types** (from the backend `#[TypeScript]` sync). Never hand-roll an interface mirroring a backend shape; type test fixtures against the generated types too.

## 6. Data display helpers (write once, reuse)

- **Empty cells/values** fall back to a shared em-dash placeholder (`EMPTY_CELL` / `emptyCell(value)`) for `null`/`''` — never render nothing.
- **Dates:** never `new Date(iso).toLocaleDateString()` directly (tz shift). One locale-aware date helper (`apps/web/src/lib/date.ts`); backend emits ISO/structured data, formatting happens in one place.
- **Money/large numbers:** one formatter (BGN/EUR, thousands separators). Procurement values are huge — make them readable.

## 7. Loading & feedback ⭐

- Any action/view that may take a moment (scrapes, detector runs, big tables, async job polling) shows a **skeleton or loading indicator**, and surfaces errors. Never leave the user staring at a frozen screen — especially since heavy backend work is async (the client polls / subscribes for results).

## 8. Icons

- **Phosphor icons** by their `XxxIcon` alias form (`InfoIcon`, `WarningIcon`, `XIcon`) — that's the standard; write it that way from the start. We never introduce the deprecated bare names (`Info`, `X`, …).

## 9. Posts feed: cache + view counts ⭐

- The home experience is a feed of **posts** (latest corruption write-ups). **Cache the posts client-side** — fetch through the `http` wrapper into a query cache (TanStack Query / SWR), serve stale-while-revalidate so the feed is instant and we don't re-hit the API on every navigation. Bust/refetch on focus or when a post is opened.
- Each post card shows its **view count**. A view is registered **once per IP** (the backend dedups by IP and `+1`s in Redis — backend.md §14); the client just renders the count and triggers the view on open. Don't increment optimistically per render — the count is authoritative from the API.
- Counts/dates/numbers go through the shared formatters (§6) — never raw.

## 10. The map & the price graph — flagship views ⭐ (CLAUDE.md §1.2)

These two are headline demo features, not nice-to-haves. Both obey every rule above (App* wrapper, i18n, formatters, tokens, loading states).

- **🗺️ Map = Mapbox.** Use **Mapbox GL** (`mapbox-gl` / `react-map-gl`), wrapped in a single **`AppMap`** component — never drop raw Mapbox JSX into a page. Token from **`VITE_MAPBOX_TOKEN`** (env, never committed; document it in `.env.example`). Markers/clusters are **coloured by sphere and by severity band** — and those colours come from the **MUI theme / Tailwind tokens** (§1), never hardcoded hex. The map is **filterable by Sphere → Category → Severity** (CLAUDE.md §1.0); clicking a marker opens the records there. Cluster at low zoom for performance; show a skeleton while tiles/data load (§7).
- **📈 Price-over-time chart.** Use **MUI X charts** in an **`AppPriceChart`** wrapper — a line of price across **snapshots** for a product/category, with the outlier tender highlighted. All money/dates through the shared formatters (§6). Loading + empty states required (§7).
- **Severity & badges.** The 🟢/🟡/🔴 severity band, sphere, category, and the punk tags (`крадене на пари`, `кофти сделки`, `шуши-муши` — CLAUDE.md §1.0.1) render through one **`AppFlagBadge`** component; all labels via i18n (Bulgarian-first), all colours from tokens.
