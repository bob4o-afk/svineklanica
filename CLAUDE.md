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

## 1. THE PROJECT — Public Procurement Watchdog

**Codename:** _ТЪРГ / OPS_ (final name TBD).
**One-liner (the roast):** _"Обществените поръчки са публични. Прозрачни — не. Ние правим намаляваме разликата — и показваме кой се надява, че няма да гледаш."_

Bulgarian public procurement (обществени поръчки) is technically public — the data sits in registries nobody reads. We **ingest it, normalize it, and automatically raise red flags** a citizen or journalist would never spot by hand. Then we make it **clickable, graphable, and embarrassing.**

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

- **Search / browse** procurements (by authority, contractor, CPV category, value, region).
- **Entity pages:** a contracting authority, a contractor/company — with their flag history.
- **Flag feed:** "what's suspicious right now," sortable, each card explains _why_ in plain Bulgarian + the source link.
- **Price graph** for a product/category over time (the snapshot story).
- **The graph view** of winner↔authority relationships for serial-winner cases.
- Built so a **non-technical citizen or journalist** can use it. Plain language, not jargon.

### 1.3 Scoring map — what each criterion demands of THIS build (§2 weights)

- **Tech & Security 30%** → it works on **real ingested data**, has a **working demo**, detectors run on actual records (not hand-picked rows).
- **Radical critical thinking 30%** → we bite **rigging/corruption mechanics**, not "transparency is nice." Each detector targets a real abuse pattern.
- **System design & UX 20%** → a citizen can find a scandal in 2 clicks.
- **Presentation & Roast 20%** → live demo pulls up a **real, named, embarrassing** Bulgarian case and lets the data do the roasting.

---

## 2. Hard rules (break these = disqualified / fails judging)

1. **All core code is written ON SITE during the ~48h.** Working in advance is **forbidden.** OSS libs, public APIs, and existing datasets are allowed — the core solution is built on site.
2. **Nothing illegal.** No unauthorized access to systems. Use **public data, scraping of public pages, OSINT of public info** only. (We have offensive-sec skills available — *authorized* targets only; **never point them at live third-party systems.**)
3. **No disinformation.** Every claim/critique is sourced to a primary record. If we can't back it, we don't ship it.
4. **Real Bulgarian targets only.** No fictional institutions/datasets for the core.
5. **Open Source license is MANDATORY.** Ship under an OSS license before the demo — _"бунтът е по-силен, когато може да бъде продължен от следващия."_ → The repo currently ships **GPL-3.0** (`LICENSE`) — strong copyleft teeth, punk-appropriate. ⚠️ **TEAM DECISION (open):** GPL-3.0 vs MIT — the original scaffold suggested MIT; we kept GPL. Pick one before the demo and make this line match the `LICENSE` file.

---

## 3. Build constraints & freedoms

- **Tech stack (mandatory):** API-first **Laravel** backend (modular — controllers · DTOs · actions · services · repositories) + a **React + TypeScript + Tailwind** frontend. **Web and mobile are the same responsive PWA — there is NO separate native app.** One mobile-first codebase serves phone → desktop, installable to the home screen, so the tool reaches many users. No org platform restriction — _"ако инструментът върши работа, използвайте го."_
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
- We'll add `skills/` and tighten these rules as the project takes shape. This file + `.claude/rules/` are the living contract.

---

## LAST. PROJECT STATUS (fill in on site)

> Update the moment the challenge is announced and details lock.

- **Codename / final name:** _TBD_
- **Chosen направление / challenge:** _TBD (announced 5 Jun)_
- **Detectors shipped for demo (from §1.1):** _TBD — pick 2–3 strong ones first_
- **Real data source(s) wired in:** _TBD (see `.claude/rules/data-sources.md`)_
- **The hero demo case (a real, named BG scandal):** _TBD_
- **The roast / one-liner for the pitch:** _TBD_
- **License:** _TBD (MIT / AGPL-3.0)_

---

_Sources: liberhack.org (home) · liberhack.org/reglament (регламент, upd. 13.05.2026) · liberhack.org/programme._
