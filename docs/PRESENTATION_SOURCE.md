# СВИНЕКЛАНИЦА / Svineklanitsa Watchdog — Presentation Source Document

> **Purpose of this file.** This is a single, exhaustive knowledge dump about the project, written to be fed to another AI that will *extract and compress* the best material into a final 10‑minute pitch + slides. It is deliberately long and over‑complete. Pull from it; don't ship it whole.
>
> **What the project is, in one line:** a punk civic‑tech web app that **automatically scrapes public Bulgarian government data, detects deals that smell like corruption, scores them with auditable rules + AI, and publishes them on a map and a feed in plain Bulgarian — with a primary‑source link behind every accusation.**
>
> **Event:** LiberHack, 5–7 June 2026, Burgas (ППМГ „Акад. Никола Обрешков"). ~48h in‑person hackathon. Theme: *Social impact & Civic tech — but make it punk.*
>
> **Compiled:** 2026‑06‑06, from a full read of the codebase across all branches.

---

## 0. HOW TO USE THIS DOCUMENT (note to the extracting AI)

The pitch is scored on four axes (see §1). When you select material, weight it toward those axes:
- **Lead with the wound and the live demo on real data** (Tech 30% + Radical 30% = 60% of the score lives there).
- **Every on‑screen claim needs a primary‑source URL** — this is both a hard rule and the punk thesis ("the rebellion is against corruption, not against the truth").
- **Be honest about status** (see §13). The single most credible thing in the room is rigor: "we flag, we don't convict." Do **not** let the pitch over‑claim a fully‑live‑on‑real‑data demo if the integration branch isn't merged at demo time — read §13 and choose the framing that matches reality on Sunday.
- The project's voice is **deadpan, factual, savage**. Big numbers, small words, black background, monospace.

---

## 1. PRESENTATION REQUIREMENTS & JUDGING CRITERIA (optimize the pitch against these)

**Format (from the LiberHack reglament):**
- **Pitch: up to 10 minutes.** Then **5 minutes of jury Q&A.** Jury decisions are final.
- Jury composition spans **tech + design + journalism + civil society** — expect hostile, well‑informed probing on data provenance and defamation.
- Presentations: **Sunday 7 June, 15:00–~19:00**, winners announced after.

**The four judging criteria and their exact weights:**

| # | Criterion | Weight | What it really tests |
|---|---|---|---|
| 1 | **Technical Mastery & Security** | **30%** | Does it actually work? **Real Bulgarian data, not samples.** Live working demo (not a video). Handles real‑world mess (Cyrillic/UTF‑8, missing values). Code survives past the presentation (public, runnable repo). |
| 2 | **Radical Critical Thinking** | **30%** | Did you bite the **systemic** problem, not a symptom? Would it provoke a real reaction from citizens/journalists/institutions? Is the insight non‑obvious and recurring? **Every critique fact‑backed — no disinformation.** |
| 3 | **System Design & UX Revolution** | **20%** | Can an **ordinary, non‑technical citizen** use it unaided? Bulgarian UI, one obvious action, solves a real recurring pain. |
| 4 | **Presentation & "Roast"** | **20%** | How sharp, funny, well‑argued is the problem presented? **Speaking with facts while provoking.** A wound‑first cold open, one unforgettable punch, a Bulgarian punk reference. |

**Hard rules (break = disqualified / auto‑zero):**
1. **All core code built ON SITE during the ~48h.** (OSS libs, public APIs, and pre‑downloaded public datasets are allowed; the *solution* is built on site.)
2. **Nothing illegal.** Public data, scraping of public pages, OSINT only. No unauthorized access, no login‑walled scraping, no rate‑abuse.
3. **No disinformation.** Every claim sourced to a primary record. No source → don't ship it.
4. **Real Bulgarian targets only.** No fictional institutions/datasets for the core.
5. **Open‑source license MANDATORY** before the demo. → **Chosen: GPL‑3.0** (copyleft — any fork stays open; "бунтът е по‑силен, когато може да бъде продължен от следващия").

**Prizes:** overall 🥇500€ / 🥈300€ / 🥉100€ (+ physical); best per category +100€ & diploma; everyone gets a certificate. There is a **направление (track)** to pick — map the project to whichever announced track fits best to also win that category.

**The pitch's own punk manifesto lines (quote these back at the jury):**
- *„Системата разчита на твоето мълчание. Бъди шумен."* (The system relies on your silence. Be loud.)
- *„Punk is the refusal to accept that broken is normal."*
- *„Не просто поправяй – разобличавай, иронизирай и преобразявай."*
- Civic close: *„Мутри вън."* (the real rallying cry of the 2020 anti‑government protests).

---

## 2. THE PROJECT — name, mission, one‑liner

- **Public / pitch name:** **СВИНЕКЛАНИЦА** ("Свинекланица Watchdog" / *Svineklanitsa Watchdog*). (Repo codename is `corruption-fucker`; the public name everywhere — email notifier, README, demo, GHCR images `svineklanitsa-api`/`-web` — is Свинекланица.)
- **Tagline:** *„корупцията на показ"* (corruption on display).
- **The roast one‑liner:** *„Обществените поръчки са публични. Прозрачни — не. Ние намаляваме разликата — и показваме кой се надява, че няма да гледаш."* (Public procurement is public. Transparent — no. We close the gap — and show who's hoping you won't look.)
- **The problem:** Bulgarian public spending (обществени поръчки, плащания) is *technically* public — but buried in clunky registries, PDFs, and databases nobody reads. Corruption hides in plain sight: "public" but invisible. A normal citizen has no realistic way to spot that the same company keeps winning rigged contracts, or that a road got "repaired" for an absurd price.
- **The thesis (radical critical thinking):** un‑queryable PDFs aren't an accident — they're a choice. *You can't audit what you can't search.* So we take the data that's already ours and throw the ugly parts in everyone's face — sharp, a little savage, but **built entirely on facts**.
- **The boundaries (non‑negotiable, and they're also the defamation shield):** public data only; **every flag links to its primary source — no source, no flag**; we flag *patterns*, we don't *convict* people ("this looks suspicious, here's the evidence, judge for yourself").

---

## 3. THE CORE MODEL — Sphere → Category → Severity (+ punk tags)

Everything in the product hangs off a **three‑level hierarchy**. Read a flag as a sentence: *"In **здравеопазване**, under **обществена поръчка**, this tender is **🔴 high (87%)** suspicious — here's why, here's the source."*

1. **Sphere (сфера)** — which part of the state. Implemented: **съдебна система** (judiciary), **здравеопазване** (healthcare), **полиция** (police), **правителство** (government), **пътно строителство** (roads), and **образование** (education, reserved). Backed by an int enum (`Sphere`, block 6000) with Bulgarian labels.
2. **Corruption category (категория)** — the *mechanism* of abuse. Core two: **обществена поръчка** (public procurement) and **нерегламентирани плащания** (off‑the‑books / unregulated payments). The pipeline also handles **конкурси за работа** (rigged job competitions), **продажба на активи** (asset sales), **одити** (audits), **имуществени декларации** (property declarations), **концесии** (concessions), and **инфраструктурни проекти** (infrastructure projects). Backed by `CorruptionCategory` (block 5000).
3. **Flag severity (band + score)** — every flagged record carries a computed **0–100 suspicion score**, shown as a band: **🟢 low / 🟡 medium / 🔴 high / 🔴 critical**. Store the %, show the band. Backed by `FlagSeverity` (block 3000) with `fromScore()` mapping (Low ≥0, Medium ≥40, High ≥70, Critical ≥90).

**Punk tags / badges ("шуши‑муши") — the editorial layer on top.** Every *published* post also gets one or more savage plain‑Bulgarian badges, separate from the formal category: **`крадене на пари`** (stealing money), **`кофти сделки`** (dodgy deals), **`шуши‑муши`** (shady/under‑the‑table — the catch‑all "something stinks"). Admin‑assigned on publish; rendered through one `AppFlagBadge`/`AppTag` component; values via i18n. The badge is the roast; the category is the evidence.

---

## 4. THE RED‑FLAG DETECTORS (this IS the product)

Each detector turns a known corruption trick into a visible, sourced signal. **Each flag is a claim, and every claim links to the primary‑source document it came from. No source → no flag.**

### 4.1 The seven detector concepts (the catalogue)
1. **💸 Price discrepancy / overpricing** — same item priced wildly differently across tenders (a laptop at 10 here, 100 there). Normalize line‑items, cluster by product, show the spread + a price‑over‑time graph.
2. **🧬 Tailor‑made specs (rigged tenders)** — conditions so specific only one bidder can qualify (the "this desk, but it must have the word *'accurate'* engraved at a specific spot" tell).
3. **🏆 Serial winner** — one bidder wins many tenders in a row, under different company names or always the same authority → a winner↔authority graph + shell‑company clusters (shared address / EIK / owner / phone).
4. **🚪 Announced‑then‑cancelled** — a tender opened then terminated by the authority (often because the "intended" bidder was about to lose) → lifecycle tracking, cancel‑after‑bids‑opened, re‑announcement with tweaked specs.
5. **🛣️ Implausible scope** — physically/financially absurd work ("repair" of a brand‑new road; quantities/unit costs that don't add up).
6. **⏰ Delayed payments** — contracted vs actually‑paid timeline; chronic late payers (powered by SEBRA payment data).
7. **📄 Copy‑paste documentation** — near‑identical tender docs, and more interestingly *deviations from the standard* (a clause inserted to favor someone).

### 4.2 What is actually BUILT (deterministic Laravel detectors)
Three production detectors are implemented in the backend `Detection` module (each implements a `Detector` contract, runs idempotently — re‑run atomically *replaces* that type's flags so retries never duplicate, reads procurement data through a DTO seam, and writes `Flag` rows with `source_urls`, sphere/category, an integer 0–100 `score`, severity band, Bulgarian explanation, and an `evidence` array of the numbers). Run via `php artisan detect:run`.

1. **`PriceDiscrepancyDetector`** — clusters every priced line‑item by a normalized product key; for clusters with ≥3 observations computes the **median** and flags any item priced **≥1.5× the median**. Score = `min(100, round((ratio−1)×50))` (1.5×→25, 2×→50, 3×→100). Evidence: product_key, price, median, ratio, currency, cluster_size.
2. **`SerialWinnerDetector`** — companies with ≥3 won tenders; score = `min(100, wins×15 + concentration_bonus)` where wins piling onto few authorities add a bonus. Evidence: EIK, win_count, distinct_authorities. EIK‑aware vs no‑EIK explanation strings.
3. **`CancelledTenderDetector`** — flags tenders with status Cancelled (score 50) or Terminated (score 70 — hard termination is the louder signal). Evidence: status, terminated flag, cancelled_at.

(The other four — tailored spec, implausible scope, delayed payment, doc‑clone — exist as enum types + i18n labels; their *detection* is covered far more deeply by the AI layer, see §6.)

### 4.3 The AI detector layer goes much further (see §6)
The Python `apps/ai` analyzer implements **far more than 3 detectors**: ~19 LLM agents + 17 deterministic feature families covering ~60 catalogued red‑flag parameters across all spheres, producing an auditable 0–100 corruption score. This is the deeper, more impressive detection story — but the auditable, deterministic Laravel detectors are the ones that are simplest to demo defensibly.

---

## 5. REAL DATA — the scraping engine (the #1 pitch asset: "real data, not samples")

> 60% of the score rewards a working demo on real Bulgarian data that bites a real problem. **This is the project's strongest, most defensible asset.** The scraping lane is Python (`apps/scraper`, a `uv` project), strictly separated from the backend; it writes NDJSON that Laravel ingests. Nothing illegal — public data, polite scraping, ingest‑first (never hit upstream live during the demo).

### 5.1 Headline number: **31 real Bulgarian data sources wired**, across 6 spheres and 8 categories
Each source is a module with a pure `parse()` function unit‑tested offline against a **committed real fixture** (real HTML/CSV/JSON slices). Every record carries a real `source_url` + UTC `fetched_at`.

**Cross‑cutting / bulk structured (the demo‑first "real data"):**
- **ted** — TED (Tenders Electronic Daily, `ted.europa.eu`), **JSON Search API v3** (`POST`, `buyer-country=BGR`): EU‑wide notices incl. above‑threshold BG tenders (title, buyer, value, CPV, dates). **Live‑verified, real BG sample committed.** Best structured source.
- **caiseop** — ЦАИС ЕОП awarded contracts via data.egov.bg (**CSV**): authority+EIK, winner+EIK, value, CPV, sign date. The **serial‑winner + overpricing dataset**. Validates EIK checksums.
- **sebra** — СЕБРА budget payments (**CSV**): actual payments by spenders (spender, recipient, amount, date). Powers the **delayed‑payments** detector.
- **egov** — data.egov.bg National Open Data Portal (**JSON**, handles data.egov + CKAN envelopes).
- **aop** — АОП/РОП Register of Public Procurement (**HTML**, historical pre‑ЕОП notices, legacy cp1251).
- **eop** — ЦАИС ЕОП search UI `app.eop.bg` (**HTML via Playwright**, JS/WAF‑heavy).
- **isun** — ИСУН 2020 EU‑funds `2020.eufunds.bg` (**HTML via Playwright**, WAF‑gated): EU‑funded beneficiaries + grant amounts.

**Healthcare (здравеопазване):** `nhif` (НЗОК tenders), `mz` (Ministry of Health tenders), `mz_jobs` (hospital director competitions), `mz_assets` (medical equipment/vehicle disposal), `ncpr` (НСЦРЛП **drug ceiling prices** — the overpricing benchmark, CSV).

**Judiciary (съдебна система):** `vss` (ВСС tenders), `prb` (Prosecutor's Office tenders), `vss_jobs` (magistrate/admin competitions), `ivss_declarations` (ИВСС magistrate property declarations), `mjs_assets` (court‑building/vehicle disposal).

**Police (полиция):** `mvr` (МВР tenders — uniforms, gear, vehicles, IT), `mvr_jobs` (police hiring competitions), `mvr_assets` (state‑asset disposal), `mvr_donations` (register of donations to МВР — influence/off‑the‑books).

**Government (правителство):** `gov_tenders` (Council of Ministers), `gov_jobs` (ИИСДА central‑admin competitions), `gov_audits` (Сметна палата / State Audit Office reports), `gov_declarations` (КПКОНПИ high‑level property declarations), `gov_concessions` (НКР National Concession Register).

**Roads / construction (пътно строителство):** `api_tenders` (АПИ Road Infrastructure Agency), `api_jobs`, `api_projects` (major projects — Хемус etc.), `mrrb_tenders` (МРРБ), `avtomagistrali_tenders` (Автомагистрали ЕАД).

> Category coverage: tenders/procurement (15 sources), payments (sebra, mvr_donations, ivss_declarations), job competitions (mz/vss/mvr/gov/api `_jobs`), asset sales (mz/mjs/mvr `_assets`), drug‑price benchmark (ncpr), audits (gov_audits), property declarations (gov_declarations, ivss_declarations), concessions (gov_concessions), infrastructure projects (api_projects).

### 5.2 Engineering that makes the data trustworthy (Q&A armor)
- **The handoff contract** (`contract.py`, Pydantic, `schema_version=1`): every record is `{source, natural_key, source_url, fetched_at, schema_version, payload}` written as one UTF‑8 NDJSON line. Laravel ingests with `php artisan ingest:run --source=<x>`, **idempotently upserting on `natural_key`** (re‑running never duplicates).
- **Provenance kept:** raw payload snapshots (`raw/<source>/<hash>.<ext>`) so we can re‑parse without re‑fetching and prove provenance; **committed demo samples** (`samples/<source>.ndjson`) so a dead upstream can't kill the pitch.
- **Cyrillic safety** (`encoding.py`): header charset → utf‑8 → chardet → cp1251 → cp1251‑with‑replace fallback (handles the windows‑1251 mojibake risk on legacy gov sites). Scraped Bulgarian values stay Bulgarian — never translated.
- **Polite + legal** (`http.py`): an **SSRF allow‑list** (only the configured public source domains — TED, data.egov.bg, АОП/РОП, СЕБРА, registryagency, etc. — never an arbitrary URL), robots.txt honored per host, **≤1 request/sec/host throttle**, exponential backoff honoring `Retry‑After`, on‑disk fetch cache, real User‑Agent. *"We expose bad actors; we don't become one."*
- **Normalization** (`normalize.py`): BG day‑first dates, BGN/EUR/USD currency + VAT handling, CPV 8‑digit extraction, and a real **EIK/БУЛСТАТ control‑digit checksum** so junk company IDs never become serial‑winner join keys.
- **Testing:** 27 scraper test files, 31 committed fixtures; an **opt‑in live‑network test** that hits TED for real and asserts Bulgarian notices come back.

### 5.3 The vectorized database (pgvector) — load‑bearing, not a buzzword
- **PostgreSQL + `pgvector`** (vector(384) columns on companies' names, tenders' and tender_items' descriptions; HNSW + cosine index). Embeddings are produced **in Python** (`uv run embed`) with a Bulgarian‑aware multilingual model (`paraphrase‑multilingual‑MiniLM‑L12‑v2`, 384‑dim, ONNX/CPU via fastembed — no GPU, no API cost), delivered as a sidecar keyed by `natural_key` (the ingest contract is unchanged).
- **Why it's load‑bearing:** semantic similarity in SQL powers (a) overpricing clustering — "the same laptop written five different ways", (b) doc‑clone detection — near‑duplicate tender docs, (c) shell/serial‑winner entity matching — company‑name variants that are really one entity, and (d) **citizen semantic search** by meaning, not exact keywords.
- **Provable in pure Python:** `uv run search --source ted "компютърни монитори"` ranks the matching notice first — no backend needed (good demo fallback).

---

## 6. THE AI / LLM CORRUPTION ANALYZER (`apps/ai`) — the deep detection brain

A separate Python lane (`procurement-analyzer`, LangChain + **Google Gemini**) reads the scraper's normalized corpus, runs deterministic feature math + parallel LLM agents, fuses them into an **auditable 0–100 corruption score**, and writes a verdict sidecar Laravel ingests. It never touches the DB.

- **Model:** Gemini via LangChain `ChatGoogleGenerativeAI` with `with_structured_output` (native JSON‑schema). Default `gemini-3.1-flash-lite`, **temperature 0** (deterministic/auditable). Degrades gracefully to a neutral result if no API key (never leaks, never crashes); tests run against a stub with **zero token spend**.
- **Architecture per record:** resolve sphere → route to a category flow (deterministic cascade, LLM fallback) → run deterministic features → run parallel LLM agents → **scorer fuses to 0–100** → aggregator writes the Bulgarian headline/explanation. **The LLM never sets the number** — it only supplies per‑signal confidences + Bulgarian rationale. This keeps the score defensible and reproducible.
- **5 sphere profiles** (healthcare, judiciary, police, government, roads), each mapping sources/categories to category flows (drugs, procurement, jobs, assets, declarations, donations, audits, concessions, projects).
- **~19 LLM analysis agents** (spec_rigging, scope, lifecycle, entity, collusion, drug_overpricing, inn_steering, rigged_competition, magistrate_competition, conflict_kinship, undervalued_sale, unexplained_wealth, gov_official_wealth, donation_influence, audit_findings, concession_abuse, project_abuse, + routers + aggregator; 23 prompt files).
- **17 deterministic feature families** (~60 catalogued red‑flag parameters grounded in **Open Contracting R001–R073, OECD bid‑rigging indicators, КЗК cartel cases, World Bank/opentender CRI, Benford's law**): competition (single/few bidders, non‑open procedure, КЗК complaint), timing, thresholds (just‑under‑threshold manipulation), pricing (CPV price outliers, round amounts, cost overrun), amendments, collusion (identical/close bids, same submission time, loser‑as‑subcontractor, bid rotation), entities (serial winner, buyer dependence), lifecycle (cancel/reissue), drug pricing (vs NCPR ceiling), assets, declarations (unexplained wealth), donations, audits, concessions, projects.
- **Scoring math:** ~25 conservative **hard‑trip rules** jump straight to 99–100/100 on strong sourced combinations (e.g. winner shares a contact with a buyer official → 100; identical bid prices + identical submission time → 100; single bidder + tailor‑made spec → 99; drug price far above NCPR ceiling + LLM confirm → 100). Otherwise a per‑family **noisy‑OR × tunable family weights**, bonus for ≥3 strong families, then a logistic → 0–100. Every signal records its weight + contribution (fully auditable).
- **Citizen‑facing levels (Bulgarian):** `Корупция` (≥85 / hard‑trip) · `Висок риск` (≥65) · `Съмнително` (≥40) · `Нисък риск` (≥20) · `Нормално` (<20).
- **Iron rule enforced in code:** `if not view.source_url: return []` — **no source → no flag.**
- **Cross‑record context:** precomputes winner win‑counts, buyer→winner pair counts + buyer‑dependence ratio, per‑CPV‑division price stats (median/MAD), and an NCPR drug index — so single‑record signals have corpus context.
- **Output:** `verdicts/<source>.ndjson` whose `flags[]` match the backend `Flag` schema 1:1 (ingest directly as Flag rows). 13 AI test files, all offline by default; opt‑in single live‑Gemini smoke test.

---

## 7. THE FRONTEND — what a citizen sees (UX = 20%, must be citizen‑usable)

One **mobile‑first responsive PWA** (React 19 + TypeScript strict + MUI v6/MUI X v7 + Tailwind) = both the web and the "mobile" experience; no separate native app. Bulgarian‑first, zero hardcoded user‑facing strings (12 i18n namespaces). ~35 reusable `App*` components, single‑source design tokens (no stray hex), 60 passing Vitest tests.

### 7.1 The two flagship visualizations
- **🗺️ The map — corruption by region.** A choropleth of **Bulgaria's 28 oblasti (NUTS3)** (built with **d3‑geo** + offline GISCO GeoJSON — token‑free and demo‑safe; note: *not* Mapbox despite the rules text). Each province shaded by flag count (darker red = more flags); a **sector filter** re‑shades by sector; hover shows region + count; **click a province → it scales up while the rest dim, then drills into that region's feed** (with the feed *prefetched during the animation* so it lands instantly). This is the "holy shit, it's happening next to me" moment.
- **📈 Price‑over‑time chart.** MUI X line chart of a product/category's price across point‑in‑time **snapshots**, with the outlier tender highlighted. The highlight uses a statistically robust **median + MAD (median absolute deviation)** detector (`MAD_THRESHOLD=3`, ignores points *below* the median so it flags overpricing not bargains, needs ≥4 points, returns null on steady price‑creep to avoid false positives). The outlier renders as a contrasting mark + a dashed labelled reference line. Demo series: a "Лаптоп 15" i5/16GB" that spikes to 4200 against a ~1400–2900 baseline.
- **🕸️ Serial‑winner network graph.** React Flow bipartite graph: companies (red, sized by win count) ↔ authorities; shell‑cluster members get a dashed border; animated weighted edges labelled with order counts; pan/zoom, read‑only.

All three sit in a shared `AppChartFrame` providing loading/error/empty states and a **primary‑source attribution footer** (every chart is a sourced claim).

### 7.2 Every page / route
- **Home (`/`)** — full‑viewport punk hero (logo + split wordmark СВИНЕ/КЛАНИЦА + tagline); on scroll: a savage two‑line headline, three stat counters, two CTAs, a search box, and a teaser of the latest 3 flags.
- **Feed (`/feed`)** — the citizen's main view: **infinite‑scroll flag feed** with faceted filters (severity · detector type · sector) and sort, all encoded in the shareable URL; a `region` filter chip; each card shows severity band · type badge · sector · punk tags + a plain‑Bulgarian "why" + source link + date.
- **Post detail (`/posts/:id`)** — badge row, a "Накратко" (TL;DR) gist, conditional CTAs (→ price chart / → network graph), neutral explanation, linked subject (authority/company/tender), evidence list (the numbers), **every primary source** as a security‑validated link, detected‑at date.
- **Authority (`/authorities/:id`)** & **Company (`/companies/:eik`)** entity pages — headline stats, flag‑history grid (row→post), and for companies a **related‑companies (shell‑cluster) section** + a "view network graph" button.
- **Search (`/search`)** — debounced global search (authorities / companies / tenders), shareable `?q=`.
- **Price (`/price/:key`)**, **Map (`/map`)**, **Network (`/network/:id`)** — the flagship views above.
- **About (`/about`)** — the credibility page: methodology + security posture (sourced / public / patterns‑not‑verdicts / OSS).
- **Admin (`/admin/*`)** — the editorial workflow (see §7.3).

### 7.3 Admin / editorial workflow ("watch it publish")
Sanctum **cookie** auth (no token in JS). A real workflow: detector produces a **draft flag** → admin **review queue** → **ReviewPanel** (verify every source with a "no reachable source → no publish" warning, edit title/explanation, assign **punk tags**, approve or reject) → on approve it **appears live in the public feed**. Plus a **Sources CRUD** registry mirroring SOURCES.md (add/edit/enable/delete with client‑side URL validation).

### 7.4 The punk aesthetic (carries the 20% Roast axis visually)
- **Palette (single source of truth, no hex anywhere else):** ink black, bone cream, **alarm red `#ff2d2d`** (the red flag), acid lime accent, rust warning. Near‑square radii (2px). Dark/light mode (follows OS until the user chooses), red text‑selection, themed scrollbar that turns alarm‑red on hover.
- **Three‑voice typography:** Manrope 800 (display/wordmark), JetBrains Mono (data/labels/nav), Inter (body) — all self‑hosted.
- **Neobrutalist craft:** hard offset shadow on hover, squared chips, blurred opaque header.
- **Custom effects** (`punk.css` + Emotion GlobalStyles): **SVG film‑grain noise** over every surface (~3% opacity), a fixed **diagonal watermark "ВИЖДАМЕ · ЗНАЕМ · ПОМНИМ"** (we see · we know · we remember) at ~4% opacity, a glitch hover, a pulsing live dot, a hard‑cut page transition, a full‑screen pulsing‑red **loading screen**.
- **PWA:** installable (real/maskable/apple‑touch icons), dismissable install banner with an iOS "Share ▸ Add to Home Screen" hint, offline shell.

---

## 8. THE BACKEND — modular Laravel 11 API (engineering credibility)

API‑first, modular bounded contexts under `modules/<Domain>/` (no Inertia; same guarded API for web + mobile). Strict layering: Route (guarded) → thin Controller → Spatie Laravel‑Data DTO (validates + authorizes) → Action/Service → Repository (only place that touches Eloquent) → queued Job → JsonResource. `#[TypeScript]` type sync to the frontend (never hand‑roll a cross‑API type).

- **Modules:** `Procurement` (tenders, authorities, companies, items, price snapshots, the ingest pipeline), `Detection` (the detectors + Flag model + enums), `Presentation` (the citizen‑facing read API / BFF), `Identity` (auth + the entire security perimeter), `Notifications` (email + subscribers), `Publishing` (the posts feed).
- **Data model:** `users`, `contracting_authorities`, `companies` (EIK natural key), `tenders` (unique on source+natural_key; lifecycle dates; CPV; value/currency/VAT; sphere/category), `tender_items`, `price_snapshots` (the price‑graph store), `ingest_records` (NDJSON landing/staging), `flags` (type, severity, score, polymorphic subject, **mandatory `source_urls`**, evidence, sphere/category, series_key, sector, region_code, view_count), `posts`, `subscribers`. **pgvector** vector(384) columns + HNSW indexes. Public IDs are **UUIDv7** (`public_id`) generated in PHP; the bigint `id` never crosses the API.
- **Enums (leha int‑backed convention):** each owns a thousands‑block, cases step by 10, Bulgarian `label()` via `__('enums.*')`, `#[TypeScript]` — TenderStatus (1000), FlagType (2000), FlagSeverity (3000), PostStatus (4000), CorruptionCategory (5000), Sphere (6000), PostTag (7000). A unit test enforces the convention.
- **Ingest pipeline:** `ingest:run --source=<x>` consumes the scraper NDJSON, upserts authorities/companies (on EIK), tags Sphere+Category at ingest (keyword + CPV rules, null when unknown — no guessing), upserts tenders (on source+natural_key), writes price snapshots, and **logs skipped/failed records with reasons** so the pitch can say honestly "ingested N, skipped M because …". Heavy work is a queued Job.
- **Async by default:** anything slow/heavy (email, bulk inserts, ingest, running a detector over the dataset) is a queued, idempotent, retry‑safe Job on Redis — the request returns fast.
- **Posts & view counts:** Redis‑backed, **IP‑deduped** view counter (one IP per item per 24h, IP stored as a salted hash for privacy), with a scheduled flusher persisting totals to Postgres — never a DB write per view.

---

## 9. SECURITY (mandatory, not optional — part of the 30% Tech & Security axis)

A transparency tool being insecure would be both embarrassing and dangerous, so security is a first‑class, *demoable* feature.

- **Every endpoint guarded.** No unguarded route: auth (Sanctum), `throttle` rate‑limit, policy authorization in the DTO/Policy (never inline `Gate::allows()`). There's a test proving an unauthorized caller is rejected per guarded endpoint. Public browse endpoints are still rate‑limited + abuse‑guarded.
- **Multi‑signal blacklist** (`BlacklistMiddleware`, runs *before* throttle/auth on every API route): fingerprints callers across **5 independent signals** (IP, a long‑lived device cookie, a client localStorage id, a JS canvas/WebGL fingerprint, and a header fingerprint that bites cookieless headless bots) — banning *all* signals at once defeats a VPN/IP rotation. All keys are salted SHA‑256 (never raw IPs — privacy). TTL'd bans (default 24h, reviewable).
- **Auto‑ban on attack signatures:** path/query/body matched against SQLi / XSS / path‑traversal / command‑injection regexes → instant ban + 403.
- **Tarpit:** rapid scanners (default >300 req/min/IP) get slowed then banned.
- **🍯 Honeypot deception (a real showstopper for the demo):** env‑driven decoy routes that no real client calls and aren't linked anywhere (`/api/admin`, `/api/.env`, `/api/internal/db-dump`, `/wp-login.php`, `/.git/config`, …). A hit (1) fingerprints + **auto‑blacklists** the caller, (2) serves **believable but FAKE data from an isolated sandbox** (deterministic fabricated Bulgarian authorities/EIKs/amounts — **never the real DB**) to waste their time, (3) logs the full interaction to a dedicated `security` channel. Defensive only — we observe attackers hitting *our* system, we never hack back.
- **Standard hardening:** parameterized queries only (no string‑concatenated SQL), CORS locked to an explicit origin list (never `*`), security headers (HSTS, X‑Content‑Type‑Options, X‑Frame‑Options DENY, CSP, Referrer‑Policy), passwords hashed (bcrypt/argon2), secrets in env only, `is_admin` not mass‑assignable, frontend source‑URL scheme validation (rejects `javascript:`/`data:`), admin pages `noindex`. (An IP whitelist that bypasses the whole perimeter for trusted ops IPs exists on the integration branch.)

---

## 10. DEVOPS / INFRASTRUCTURE (prod‑ready, mostly "flip it on")

- **Everything in Docker.** Dev `docker-compose.yml` brings up the full stack with one command: **proxy** (Caddy, auto‑HTTPS), **app** (Laravel API), **queue** worker, **scheduler**, **web** (Vite), **db** (`pgvector/pgvector:pg16`), **redis**, **mailpit** (email capture), plus on‑demand **scraper** and **ai** profiles. Multi‑stage Dockerfiles; prod backend runs on **FrankenPHP** (concurrent, graceful reload — enables real zero‑downtime deploys). `make` targets: build / up / down / migrate / seed / test / lint / test‑be / test‑fe.
- **CI (GitHub Actions `ci.yml`, every push + PR):** Postgres(pgvector)+Redis service containers → backend Pest suite on real Postgres + Pint lint; frontend typecheck + lint + Vitest + build. A red suite blocks merge.
- **Release (`release.yml`, on semver tag `vX.Y.Z`):** runs the full CI gate → builds + pushes two prod images to **GHCR** (`svineklanitsa-api`, `svineklanitsa-web`, tagged version + latest) → optional **zero‑downtime auto‑deploy** to a VM over SSH (migrate‑before‑swap, `docker rollout`, dynamic Caddy upstreams) → **emails a release notification** ("Свинекланица Watchdog" — start/success/failure, with a git‑log changelog since the last tag) over SMTP, all secrets in Actions secrets.
- **Deploy (Stage 1):** single VM + `docker-compose.prod.yml` pulling GHCR images; **Caddy** terminates TLS (free auto‑renewing Let's Encrypt on a real domain); `/_health` healthchecks; runbook in `DEPLOY.md`.
- **Kubernetes (Stage 2 stretch):** full manifests in `deploy/k8s/` (Deployments per component, **HPA** on the API, **CronJob** scheduler, **Ingress + cert‑manager** TLS, ConfigMap/Secret split) — explicitly *not needed* for the hackathon, but it exists.
- **Monitoring (optional overlay):** Prometheus + cAdvisor + node‑exporter + Grafana + Uptime Kuma (hits `/_health`); Sentry for app errors (env‑gated, tree‑shaken out when no DSN); a dedicated daily `security` log channel for every auto‑ban and honeypot hit; a queued, rate‑limited email alert on unhandled 5xx errors.

---

## 11. THE COMPLETE TECH STACK (for the "it's real" 60 seconds)

- **Frontend:** React 19, TypeScript (strictest config — `exactOptionalPropertyTypes` etc.), MUI v6 + MUI X v7 (charts, DataGrid), Tailwind (Preflight off), React Router v6, TanStack Query v5, i18next (Bulgarian‑first), d3‑geo (map), React Flow (graph), Phosphor icons, Vite, Vitest, MSW, vite‑plugin‑pwa, Sentry. Installable PWA.
- **Backend:** Laravel 11, API‑first modular DDD, Spatie Laravel‑Data DTOs, Sanctum auth, queued Jobs on Redis, `#[TypeScript]` type sync, Pest tests.
- **Data:** PostgreSQL 16 + **pgvector** (384‑dim multilingual embeddings, HNSW/cosine) + Redis.
- **Scraping:** Python 3.12 (`uv`), httpx + BeautifulSoup/lxml + Playwright (for JS/WAF sites), Pydantic contract, chardet (Cyrillic), fastembed (ONNX embeddings).
- **AI:** Python + LangChain + Google Gemini (structured output, temperature 0, graceful degradation).
- **Infra:** Docker / Docker Compose, FrankenPHP, Caddy (auto‑TLS), GitHub Actions CI/CD, GHCR, optional K8s, Prometheus/Grafana/Sentry.
- **License:** GPL‑3.0 (copyleft).

---

## 12. HOW EACH FEATURE MAPS TO THE JUDGING CRITERIA (so the pitch can be deliberate)

| Criterion (weight) | What to show |
|---|---|
| **Tech & Security 30%** | Live demo on **real TED/ЦАИС ЕОП data**; the ingest pipeline (idempotent, Cyrillic‑safe, honest skip‑logging); pgvector semantic search; the **honeypot serving fake data to an attacker live**; CI green / public runnable repo / Docker‑one‑command. |
| **Radical Critical Thinking 30%** | The thesis ("you can't audit what you can't search"); detectors that bite *mechanisms* of rigging (serial winner, overpricing, cancelled‑after‑bids); the auditable 0–100 score grounded in OCP/OECD/КЗК; **"we flag, we don't convict."** |
| **System Design & UX 20%** | Two clicks to a scandal: Map → click region → feed → post → source. Bulgarian plain language, one obvious action, mobile PWA, faceted filters, the editorial "watch it publish" flow. |
| **Presentation & Roast 20%** | Punk aesthetic (alarm‑red, film grain, watermark), the price‑spike "the math caught it" moment, the network graph lighting up, the punk tags (`шуши‑муши`), and a Bulgarian punk close (*„Мутри вън."*). |

---

## 13. HONEST STATUS & CAVEATS (read before pitching — credibility depends on this)

The project is ~90% built, but the pieces live across branches. **Do not over‑claim a fully‑live‑on‑real‑data trunk unless it's merged at demo time.** Decide the framing on Sunday based on reality.

- **What's on `main`:** the 31‑source Python scraper, the AI analyzer, pgvector schema, the full security perimeter (honeypot/blacklist/Sanctum), the ingest skeleton, a generic CMS posts API, and the full frontend (running on **MSW mocks**, not a live backend).
- **What's on the unmerged branch `feat/connecting-backend-frontend`:** the **3 deterministic detectors**, the **Presentation read API** (the endpoints the frontend needs: flag‑posts feed/detail, authorities, companies, price‑series, serial‑winner graph, region aggregate, search, stats), the sphere/category/score columns on flags, and price‑snapshot writing. The merge into main is assessed as **EASY (sub‑hour, 2 trivial frontend conflicts)** — but until done, `main` doesn't serve real flags end‑to‑end.
- **Frontend data:** the React app currently renders **deterministic invented fixtures via MSW** (realistic Bulgarian names + punk headlines, *explicitly not real institutions*). To go live: merge the branch, `composer sync:api-types`, set `VITE_ENABLE_MOCKS=false`, ingest a real source, run the detectors.
- **Detectors built:** 3 of 7 in deterministic Laravel (price discrepancy, serial winner, cancelled); the AI layer covers far more but needs its verdicts ingested as flags.
- **The map is a d3‑geo choropleth of the 28 oblasti, not Mapbox** (offline, token‑free — describe it accurately).
- **The hero demo case (a real, named, embarrassing BG procurement scandal) is still TBD** — pick one real case from ingested TED/ЦАИС ЕОП data on site and build the cold open around it. This is the single most important missing piece for the pitch.
- **Pre‑hackathon idea‑exploration docs exist** (election‑anomaly forensics, media‑ownership X‑ray, ЗДОИ scoreboard) in `docs/research/` — these are **abandoned earlier directions**, not this project. Don't pitch them; they're only useful as a *backup* if a demo catastrophe forces a pivot (the election‑anomaly POC is the most ready fallback).

---

## 14. PITCH AMMUNITION (raw material for the extracting AI)

### 14.1 The 10‑minute structure (rehearse to ~9:00, leave buffer)
| Time | Beat | Goal |
|---|---|---|
| 0:00–0:45 | **Cold open** | One brutal, true, sourced fact. No team intro, no agenda slide. Just the wound. |
| 0:45–2:00 | **The real problem** | The systemic failure: "public but unsearchable is a choice." |
| 2:00–2:45 | **The stakes** | Make it the room's money: €X per citizen of Burgas. |
| 2:45–6:30 | **THE LIVE DEMO** | The tool on real BG data doing the damning thing. One clean narrative path: input → it works → the damning output → click through to the source URL. |
| 6:30–7:30 | **The punch** | The single most shocking output. Dwell on it (the price spike / the network graph / the red region). |
| 7:30–8:30 | **It's real & it's yours** | Real source named, **open‑source (GPL‑3.0)**, runs locally in Docker, built in 48h. |
| 8:30–9:00 | **Call to action** | "A journalist opens this tomorrow and has 5 leads by lunch. A citizen files a complaint in one click." |
| 9:00–10:00 | **Buffer** | Never run to the buzzer. Silence is power. Close on *„Мутри вън."* |

### 14.2 Cold‑open formula
`[a number / contradiction] + [pause] + [here they are]`. Example shape (fill with a real ingested case): *"This laptop cost the state 1 400 лева. [pause] The same laptop, same year, cost another ministry 4 200. We didn't pick it — the data did."*

### 14.3 The "roast" toolkit
- **Deadpan > shouting.** State the absurd fact flatly, let the room laugh.
- **Juxtaposition.** Put the official claim next to the real number — the gap is the joke.
- **Name the pattern, not the person.** *"We won't name them. We don't have to — the public record does."* (Punk AND defamation shield.)
- **Callback** the cold‑open line at the end.
- **Punk quotes as spine:** the reglament's own lines + *„Мутри вън."*

### 14.4 Surviving Q&A (the jury includes journalists who will probe)
| Likely question | Prepared answer |
|---|---|
| "Is the data real / where's it from?" | Name the exact public source (TED / ЦАИС ЕОП / data.egov.bg), open re‑use, URL ready. |
| "Isn't this just an anomaly, not proof?" | **Agree first**, then reframe: *"Correct — we flag, we don't convict. We hand journalists a sorted list of where to look."* |
| "Aren't you defaming people?" | *"Only public records, only public‑office holders in official capacity, every claim links to source. We surface patterns; conclusions are the viewer's."* |
| "What's novel vs Bivol/BIRD?" | Credit the precedent, then the delta: automated multi‑sphere detection + auditable scoring + a map a citizen can use + open‑source + the punk presentation. |
| "Could this be weaponized for disinfo?" | *"Sourced + factual + no de‑anonymizing. Punk is fact‑based. No source → no flag, enforced in code."* |
| "Does it actually run, or is the demo faked?" | Offer to run it again live on a value the jury picks. |

### 14.5 "Safe to name" reference (US/UK Global Magnitsky — public record, cite freely)
Useful only if a serial‑winner / shell case happens to touch one of these sanctioned figures; otherwise stick to "name the pattern, not the person." 2021: **Delyan Peevski** (say "sanctioned," not "convicted"), **Vassil Bozhkov**, **Ilko Zhelyazkov**. 2023: **Vladislav Goranov** (ex‑finance min.), **Rumen Ovcharov** (ex‑energy min.), **Nikolay Malinov**, **Aleksandar Nikolov** & **Ivan Genov** (ex‑Kozloduy NPP).

### 14.6 Slide rules
Black background, monospace, **one idea per slide, big numbers / small words**. Show real screenshots + real URLs, never lorem‑ipsum mockups (the reglament explicitly mocks "beautiful content‑free slides"). The tool IS the deck — spend pixels on the live demo.

### 14.7 Roles for the 15 minutes (if 3 presenters)
- **Voice** — cold open, problem, punch, close (sharpest talker).
- **Driver** — runs the live demo, knows every keystroke, has the **fallback screen‑recording** for dead Wi‑Fi.
- **Defender** — owns data provenance, answers hostile Q&A, every source URL memorized.

---

## 15. HEADLINE NUMBERS (one‑glance stat bank for slides)

- **31** real Bulgarian data sources wired, across **6** spheres and **8** corruption categories.
- **7** red‑flag detector types defined; **3** deterministic detectors built + an **AI layer of ~19 LLM agents + 17 feature families (~60 catalogued red‑flag parameters)**.
- Auditable **0–100** corruption score with ~25 hard‑trip rules, grounded in **Open Contracting (R001–R073), OECD bid‑rigging, КЗК cartel cases, World Bank CRI, Benford**.
- **384‑dim** multilingual embeddings in **pgvector** (semantic search + clustering, CPU‑only, zero API cost).
- **TED is a live structured JSON API**; ЦАИС ЕОП / СЕБРА / NCPR are CSV open data — all public, no auth.
- Frontend: **~35** reusable components, **12** Bulgarian i18n namespaces, **60** passing tests; installable **PWA**, dark/light, two flagship data‑viz + a network graph.
- **5** independent abuse‑fingerprint signals; a **honeypot** that serves fake data to attackers; the **whole stack runs with one `make up`** and ships under **GPL‑3.0**.
- Total automated test coverage: **~40** Python test files (27 scraper + 13 AI) + 9 backend module test suites + 60 frontend tests.

---

*End of source document. Pull the sharpest, truest material; lead with the wound and the live demo; keep every claim sourced.*
