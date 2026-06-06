# CLAUDE.md — L1BERH4CK / LiberHack OPS

> _"Не просто поправяй – разобличавай, иронизирай и преобразявай."_
> _"Системата разчита на твоето мълчание. Бъди шумен."_

This file is **authoritative** for any work done in this folder during **LiberHack** (5–7 June 2026, Burgas).
It encodes (a) the official rules so we don't get disqualified or score badly, and (b) **what we're actually building**. Read it before writing code. Update it as the project sharpens.

**Engineering standards live in `.claude/rules/`** — read them before touching code:
- `.claude/rules/backend.md` — modular Laravel: controllers · DTOs · actions · services · repositories, async jobs, guarded endpoints.
- `.claude/rules/frontend.md` — React + TypeScript + **Tailwind**, reusable `App*` components, **web + mobile**, no hardcoded strings.
- `.claude/rules/scraping.md` — **Python** scraping (`apps/scraper`) → NDJSON ingest contract → Laravel ingests. **Scraping is Python, not Laravel.**
- `.claude/rules/security.md` — every endpoint guarded, rate limiting, bot/abuse blacklist, CORS, input sanitization, password hashing.
- `.claude/rules/devops.md` — Docker build, GitHub Actions (tests on push, email notifier on version tag).
- `.claude/rules/data-sources.md` — the procurement data sources + sourcing discipline (the heart of this project).

> **Three lanes, one job each:** **frontend** (`apps/web`, React) · **backend** (repo root, Laravel) · **scraping** (`apps/scraper`, Python). They connect through two seams: scraping →(NDJSON)→ backend →(`#[TypeScript]` types)→ frontend. Each lane has its own `CLAUDE.md`/`AGENTS.md` — **stay in your lane.**
> **All AI tools** (Claude Code, Cursor, Gemini, Codex/Copilot) read these rules — `GEMINI.md`/`AGENTS.md`/`.cursorrules` point here. An agent **allowlist** is in `.claude/settings.json` (safe commands auto-run; `rm`/`git push`/destructive DB ops gated). Token/limit tactics + copy-paste prompts: `docs/ai_usage_guide.md`, `docs/prompt_library.md`.

---

## 0. The one-line mission

**Theme:** _Social impact & Civic tech — but make it punk._
Civic tech usually looks like boring PDFs, endless grant forms, and apps with a cute logo and a three-bullet mission. Good intentions aren't enough. We build tools that **anger, mobilize, and make people act.**

Punk isn't an aesthetic. **Punk is the refusal to accept that broken is normal.**

What that means for every decision in this repo:
- **Real targets.** Real Bulgarian institutions, real sites, real data, real processes. **No invented examples, no hypothetical problems, no pretty slides without substance.**
- **Bite the real problem, not the symptom.** Could this provoke a genuine reaction from citizens, journalists, or institutions?
- **Punk is built on facts.** No disinformation. The rebellion is against the status quo, **not against the truth.** Every claim is backed by a primary-source link.
- **Transparency as a weapon.** Expose what's fundamentally broken; satirize the dysfunctional.

---

## 1. THE PROJECT — СВИНЕКЛАНИЦА / Svineklanitsa Watchdog

**Codename / name:** **СВИНЕКЛАНИЦА** ("Свинекланица Watchdog"). _(This is now the public/pitch name — the email notifier, README and demo all say **Свинекланица Watchdog**, NOT "LiberHack Watchdog".)_
**One-liner (the roast):** _"Обществените поръчки са публични. Прозрачни — не. Ние намаляваме разликата — и показваме кой се надява, че няма да гледаш."_

Bulgarian public spending (обществени поръчки, плащания) is technically public — the data sits in registries nobody reads. We **ingest it, normalize it, and automatically raise red flags** a citizen or journalist would never spot by hand. Then we make it **clickable, mappable, graphable, and embarrassing.**

### 1.0 How it's organized — Sphere → Category → Flag severity ⭐ (the core model)

Everything in the product hangs off this **three-level hierarchy**. It's how the user browses, filters, and how a flag is described:

1. **Sphere (сфера)** — the part of the state where the rot lives. **Demo focus: `съдебна система` (judiciary), `здравеопазване` (healthcare), `полиция` (police).** A sphere is the top-level filter and the thing the map colours by.
2. **Corruption category (категория корупция)** — _the mechanism_ of abuse inside a sphere. **For now exactly two:** `обществена поръчка` (public procurement) and `нерегламентирани плащания` (unregulated / off-the-books payments). More categories slot in later (e.g. `конкурси за работа` — see §1.4).
3. **Flag severity (% as low / medium / high)** — every flagged record carries a computed **suspicion score** rendered as a band: **🟢 low / 🟡 medium / 🔴 high** (store the underlying 0–100 %; show the band + the %). The detectors (§1.1) are what compute this score; the band is what the citizen sees.

> Read it as a sentence: _"In **здравеопазване**, under **обществена поръчка**, this tender is **🔴 high (87%)** suspicious — here's why, here's the source."_
> **Backend mapping:** `Sphere` and `CorruptionCategory` are **int-backed enums** (leha convention, backend.md §9.5 — own thousands-block, +10 steps, Bulgarian `label()`). A `Flag` carries `sphere`, `category`, and `severity` (the band) + `score` (the %).

### 1.0.1 Post tags / badges — the punk layer ⭐ ("шуши-муши")

Spheres/categories are the _serious_ taxonomy. On top, **every published post gets one or more punk tags** — short, savage, plain-Bulgarian labels that say what it _really_ is. These are display badges (`AppFlagBadge` / `AppTag`), separate from the formal category:

- **`крадене на пари`** (stealing money)
- **`кофти сделки`** (dodgy deals)
- **`шуши-муши`** (shady/under-the-table — the catch-all "something stinks" badge)
- _(extend the list as cases demand — keep them punchy, factual, never libellous)_

Tags are assigned when a post is published (admin-authored, backend.md §14). They're a curated editorial layer **on top of** the computed sphere/category/severity — the badge is the roast, the category is the evidence. Tag values live in i18n (Bulgarian-first), rendered through `AppFlagBadge`.

### 1.1 The red-flag detectors (this IS the product)

Each detector turns a real pattern of abuse into a visible, sourced signal. Prioritized for the demo:

1. **💸 Price discrepancy / overpricing.** Same item across tenders priced wildly differently (a laptop at 10 in one order, 100 in another). → Normalize line-items, cluster by product, show the spread. **Snapshots + a price-over-time graph** so you can watch a price creep.
2. **🧬 Tailor-made specs (rigged tenders).** Conditions so specific only one product/bidder can qualify — the "this desk, but it must also have the word _'accurate'_ engraved at a specific spot" tell. → Flag specs with suspiciously narrow / non-standard requirements vs the category norm.
3. **🏆 Serial winner.** One bidder wins many tenders in a row — under **different company names**, or always the **same company with the same contracting authority**. → Build a winner→authority graph, surface streaks and shell-company clusters (shared address / EIK / owner / phone).
4. **🚪 Announced-then-cancelled.** A tender opened then **terminated by the contracting authority** — frequently because the "intended" bidder was about to lose. → Track lifecycle; flag cancel-after-bids-opened and re-announcements with tweaked specs.
5. **🛣️ Implausible scope.** Physically/financially absurd work — "repair" of a brand-new road in Belitsa where only 2 layers get replaced; quantities or unit costs that don't add up. → Rule-based + cross-reference checks (asset age, prior contracts on the same object).
6. **⏰ Delayed payments.** Contracted vs actually-paid timeline; chronic late payers. → Surface payment lag per authority/contractor.
7. **📄 Copy-paste documentation.** Tender docs are near-identical (they follow the standard most of the time). → Detect template reuse and, more interestingly, **deviations from the standard** (a clause inserted to favor someone).

> Each flag is a **claim**, and every claim links to the **primary-source document/record** it came from. No source → no flag. (See `.claude/rules/data-sources.md`.)

### 1.2 What the user sees (UX — 20% of score, must be citizen-usable)

- **Filter by Sphere → Category → Severity** (§1.0) — the primary navigation. Every list/feed is filterable by all three.
- **🗺️ THE MAP (Mapbox) — flagship feature.** A map of Bulgaria showing **where** the suspicious deals actually happen, **filterable by sphere / category / severity**. Markers/clusters coloured by sphere, sized/coloured by severity; click a point → the records there. This is the "holy shit, it's happening next to me" moment of the demo. **Mapbox GL** (token in `VITE_MAPBOX_TOKEN`); pins come from each record's region/municipality/coords (geocode authority address or municipality at ingest). See `.claude/rules/frontend.md`.
- **📈 Price-over-time graph with snapshots — flagship feature.** For a product/category, a line of price over time built from **point-in-time snapshots** (e.g. "what did a laptop cost across 2026"). When one tender sits far above/below the line → that's the signal. Each snapshot stores `(item, tender, price, captured_at, source_url)` so the curve is real, not reconstructed (data-sources.md §2).
- **Search / browse** records (by authority, contractor, CPV category, value, region, **sphere/category**).
- **Entity pages:** a contracting authority, a contractor/company — with their flag history + their points on the map.
- **Flag / post feed:** "what's suspicious right now," sortable, each card shows its **sphere · category · severity band + punk tags** (§1.0.1) and explains _why_ in plain Bulgarian + the source link.
- **The graph view** of winner↔authority relationships for serial-winner cases.
- Built so a **non-technical citizen or journalist** can use it. Plain language, not jargon.

### 1.3 Scoring map — what each criterion demands of THIS build (§2 weights)

- **Tech & Security 30%** → it works on **real ingested data**, has a **working demo**, detectors run on actual records (not hand-picked rows).
- **Radical critical thinking 30%** → we bite **rigging/corruption mechanics**, not "transparency is nice." Each detector targets a real abuse pattern.
- **System design & UX 20%** → a citizen can find a scandal in 2 clicks.
- **Presentation & Roast 20%** → live demo pulls up a **real, named, embarrassing** Bulgarian case and lets the data do the roasting.

### 1.4 Idea backlog — rigged job competitions (РУО / education) 🧠

A future **corruption category** (and a strong candidate sphere = `образование`): **нагласени конкурси за работа.** In education, posts are advertised through the **РУО** (Регионално управление на образованието) — and **its archive** is the goldmine. The tell is an advert engineered for one pre-chosen person:

- **Absurdly short application deadline** (опит to stop anyone else applying in time).
- **Reference to чл. 67** (specific hiring article) combined with…
- **Hyper-specific required qualification** + oddly **specific personal characteristics** — written to match exactly one CV.

> ⚖️ **Nuance, not a witch-hunt:** sometimes a narrow spec is legitimate. The flag fires on the **combination**: `short deadline + чл. 67 + ultra-specific qualification + narrow characteristics`. We surface the pattern with the source advert; we don't convict (mirrors the "rigged specs" detector §1.1.2, applied to hiring). Scrape **public РУО archives only** (data-sources.md discipline).

_Not in the first demo cut unless time allows — but the model (§1.0) already fits it: new sphere `образование`, new category `конкурси за работа`, same severity bands + map + tags._

---

## 2. Hard rules (break these = disqualified / fails judging)

1. **All core code is written ON SITE during the ~48h.** Working in advance is **forbidden.** OSS libs, public APIs, and existing datasets are allowed — the core solution is built on site.
2. **Nothing illegal.** No unauthorized access to systems. Use **public data, scraping of public pages, OSINT of public info** only. (We have offensive-sec skills available — *authorized* targets only; **never point them at live third-party systems.**)
3. **No disinformation.** Every claim/critique is sourced to a primary record. If we can't back it, we don't ship it.
4. **Real Bulgarian targets only.** No fictional institutions/datasets for the core.
5. **Open Source license is MANDATORY.** Ship under an OSS license before the demo — _"бунтът е по-силен, когато може да бъде продължен от следващия."_ → **DECIDED: GPL-3.0** (`LICENSE`) — strong copyleft teeth, punk-appropriate (any fork must stay open). Aligned across `LICENSE`, `apps/web/package.json` (`GPL-3.0-or-later`), and README §License.

---

## 3. Build constraints & freedoms

- **Tech stack (mandatory):** API-first **Laravel** backend (modular — controllers · DTOs · actions · services · repositories) + a **React + TypeScript + Tailwind** frontend. **Web and mobile are the same responsive PWA — there is NO separate native app.** One mobile-first codebase serves phone → desktop, installable to the home screen, so the tool reaches many users. No org platform restriction — _"ако инструментът върши работа, използвайте го."_
- **Data layer: a vectorized database.** PostgreSQL + **`pgvector`** + Redis. Text (item descriptions, tender docs, company names) is embedded into vectors at ingest so we can do **semantic similarity in SQL** — this powers the overpricing clustering, doc-clone detection, shell/serial-winner entity matching, and semantic search. Embeddings are computed in the Python ingest layer (Bulgarian-aware multilingual). Not optional — several detectors depend on it.
- **Target = prod-ready, max scope, but efficiency-first.** The prod infra is already scaffolded (Docker, CI, Caddy TLS, health, prod compose, K8s, monitoring) — so prod-readiness is mostly *flipping it on*. Build by **payoff-per-hour**: do Tier 0→1, interleave cheap prod wins, treat heavy infra as stretch. Ranking + Definition of Done: **`docs/BUILD_PLAN.md`**. "Biggest scope" = deep+wide **within procurement**, not across many domains.
- **Ship via Docker; CI on GitHub Actions.** Everything builds and runs in Docker. Actions runs the **test suite on every push**, and on a version **tag** builds + fires an **email notifier** to the user. See `.claude/rules/devops.md`.
- **Security is mandatory, not optional.** Every endpoint guarded by middleware/policies, rate-limited, inputs sanitized, bots/abusers blacklisted, CORS locked down, passwords hashed+salted. See `.claude/rules/security.md`.
- **Reusability / abstraction is a goal**, not a nice-to-have: program to interfaces, extract shared logic, no copy-paste — on both backend and frontend.
- **Wire to real data EARLY.** 60% of the score is content. A polished mock loses to an ugly thing pulling live records.
- **Ingest-first architecture:** scrape/import → normalize into our DB → run detectors → serve. Don't hit upstream live during the demo.
- Keep an **always-working `main`** + a **known-good demo dataset cached locally** in case a source goes down mid-pitch.
- UX must be usable by a non-technical citizen. No CLI-only deliverable as the main artifact.
- Reserve time + craft for the pitch: it's a **"roast"** — sharp, funny, fact-backed.

---

## 4. Logistics (plan time correctly)

- **Format:** ~48h, in person. Old building of ППМГ „Акад. Никола Обрешков", Burgas.
- **Team:** 2–6 people, ages 14–25.
- **Schedule:** Fri 5 Jun — 16:00 reg · 16:30 opening + challenges + team forming · ~17:00–21:00 dev starts. Sat 6 Jun — 09:00–21:00 dev + mentors. Sun 7 Jun — 09:00–14:00 finalize · **15:00–~19:00 presentations + winners.**
- **Pitch:** up to **10 min + 5 min jury Q&A.** Jury decisions final.
- There is a **направление** — pick it so we can clearly win (extra 100€ + diploma) while still aiming at the overall content criteria. Map our procurement project to whichever announced направление fits best.
- **Prizes:** overall 🥇500€ 🥈300€ 🥉100€ (+ physical); best per category +100€ & diploma; everyone gets a certificate.
- **Mentors** guide, don't solve; can't be jury.

---

## 5. How I (Claude) should work in this repo

- **Default to a working demo on real data over architecture purity.** It's a 48h sprint. Follow `.claude/rules/` for the *spirit* of clean code, but ship.
- **Every feature must trace to a detector (§1.1) or a judging weight (§1.3).** If it doesn't, question it.
- **Sourcing discipline is non-negotiable:** every flag links to its primary source. Keep `SOURCES.md` of every dataset/endpoint we pull from. See `.claude/rules/data-sources.md`.
- **Legal/ethical guardrail:** public data + authorized targets only. If a task drifts toward unauthorized access, **stop and flag it.**
- **Keep `main` demo-able at all times**; cache a real-data snapshot so a dead upstream can't kill the pitch.
- **Add the OSS `LICENSE` before the demo.**
- **Common commands live in [`commands.txt`](commands.txt)** (repo root) — build/up, migrate, seed, **tests (always the isolated `liberhack_test` DB)**, `ingest:run`, route inspection, type-sync. Pull commands from there; keep it updated when a workflow changes. **Never run bare `php artisan test` in the dev container** — it migrate:fresh-wipes the dev DB; use `make test-be` or the `-e DB_DATABASE=liberhack_test` form.
- We'll add `skills/` and tighten these rules as the project takes shape. This file + `.claude/rules/` are the living contract.

---

## 6. TODO — the new direction (Свинекланица) ☑️

Concrete work items from the latest direction change. Tick as shipped.

**Core model (§1.0) — do first, everything hangs off it:**
- [ ] `Sphere` enum (int-backed, leha block) — `съдебна система`, `здравеопазване`, `полиция` (+`образование` later).
- [ ] `CorruptionCategory` enum — `обществена поръчка`, `нерегламентирани плащания` (start with these two).
- [ ] `FlagSeverity` band — 🟢 low / 🟡 medium / 🔴 high — backed by a stored `score` (0–100 %). Detectors compute `score`; band is derived.
- [ ] Add `sphere`, `category`, `score`, `severity` to the `Flag` schema + migration; `#[TypeScript]` sync.
- [ ] Filter API: list flags/posts by sphere + category + severity (guarded, rate-limited).

**Punk tags / badges (§1.0.1):**
- [ ] Post tags: `крадене на пари`, `кофти сделки`, `шуши-муши` (extensible). Admin assigns on publish.
- [ ] `AppFlagBadge` / `AppTag` component renders sphere · category · severity · punk tags; values via i18n.

**🗺️ Map (Mapbox) — flagship:**
- [ ] Frontend `AppMap` (Mapbox GL, `VITE_MAPBOX_TOKEN`) — markers/clusters coloured by sphere, by severity.
- [ ] Filterable by sphere / category / severity; click marker → records there.
- [ ] Ingest: geocode authority/municipality → lat/lng (or municipality centroid) so records have coords.

**📈 Price-over-time graph — flagship:**
- [ ] Snapshot store `(item, tender, price, captured_at, source_url)` + ingest writes snapshots.
- [ ] `AppPriceChart` (MUI X chart) — price line per product/category; highlight the outlier tender.

**Naming:**
- [x] Email notifier renamed **LiberHack Watchdog → Свинекланица Watchdog** (`.github/workflows/release.yml`).
- [x] GHCR image names `liberhack-api/-web` → `svineklanitsa-api/-web` (release.yml, prod compose, k8s, commands.txt, package.json) + README/commands.txt titles. _(DB name `liberhack`/`liberhack_test` and the k8s namespace are intentionally unchanged.)_

**Data sources:** verified, URL-by-URL list (mapped to Sphere → Category) is in [`SOURCES.md`](SOURCES.md). Demo-first source = **TED** (no-auth bulk); payments via **СЕБРА**; companies via **Търговски регистър** (EIK).

**Backlog (§1.4):** РУО rigged-job-competitions category (`образование` sphere) — design later.

---

## LAST. PROJECT STATUS (fill in on site)

> Update the moment the challenge is announced and details lock.

- **Codename / final name:** **Свинекланица / Svineklanitsa Watchdog**
- **Chosen направление / challenge:** _TBD (announced 5 Jun)_
- **Detectors shipped for demo (from §1.1):** _TBD — pick 2–3 strong ones first_
- **Real data source(s) wired in:** _TBD (see `.claude/rules/data-sources.md`)_
- **The hero demo case (a real, named BG scandal):** _TBD_
- **The roast / one-liner for the pitch:** _TBD_
- **License:** **GPL-3.0** (decided) — `LICENSE` + `apps/web/package.json` (`GPL-3.0-or-later`)

---

_Sources: liberhack.org (home) · liberhack.org/reglament (регламент, upd. 13.05.2026) · liberhack.org/programme._
