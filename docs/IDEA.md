# СВИНЕКЛАНИЦА (Svineklanitsa Watchdog) — the idea (plain words)

> A living doc for the team to review and mark up. The engineering rules live in `CLAUDE.md` + `.claude/rules/`; this file is just **what we're building and why**, in plain language. Edit freely.
> **Name:** the project is now **Свинекланица** ("Свинекланица Watchdog") — that's what the email notifier, README and demo say.

## In one sentence
A **website that automatically digs through public Bulgarian government data, finds the deals that smell like corruption, and publishes them on a map in plain language — with a link to the proof behind every accusation.**

## How it's organized: Sphere → Category → Severity
Everything hangs off a **three-level hierarchy** (the core model — `CLAUDE.md` §1.0):
1. **Sphere (сфера)** — which part of the state: **съдебна система, здравеопазване, полиция** (demo focus; `образование` later).
2. **Corruption category (категория)** — the mechanism: **обществена поръчка** and **нерегламентирани плащания** (two for now).
3. **Severity** — a suspicion score shown as a band: **🟢 low / 🟡 medium / 🔴 high** (store the 0–100 %, show the band).

On top of that, every published post gets **punk tags / badges** — `крадене на пари`, `кофти сделки`, `шуши-муши` — the savage plain-Bulgarian label for what it really is.

## Two flagship views
- **🗺️ A map (Mapbox)** of **where** the dodgy deals happen, filterable by sphere/category/severity — the "it's happening next to me" moment.
- **📈 A price-over-time graph** built from snapshots (e.g. what a laptop cost across 2026) — when one tender sits far off the line, something's wrong.

## The problem
Government spending in Bulgaria *is* technically public — but it's buried in clunky registries, PDFs, and databases nobody reads. So corruption hides in plain sight: it's "public," but invisible. A normal citizen has no realistic way to spot that the same company keeps winning rigged contracts, or that a road got "repaired" for an absurd price.

## What it does (what a visitor sees)
A clean, Bulgarian-language website where an ordinary person — no technical skill — can:
- **Browse a feed of red flags**: "here's what looks suspicious right now," each card explaining *why* in plain words.
- **Search** any company or institution and see its history of suspicious activity.
- **Click any claim and reach the original document** that proves it. No source = it doesn't get published.
- Use it just as easily on a **phone** as on a desktop.

## How it works (3 steps)
1. **Scrape** — automatically pull public procurement data (tenders, who won, prices, contracts) from official sources.
2. **Detect** — automated "detectors" scan that data for patterns that signal abuse.
3. **Publish** — anything flagged is shown on the site, in plain Bulgarian, with the source link.

## Under the hood: our database is vectorized
We run **PostgreSQL with `pgvector`** — a **vectorized database**. Alongside the normal columns, the meaningful text (tender item descriptions, tender documents, company names) is stored as **embedding vectors**, so we can ask "what's *similar in meaning*" directly in the database. This isn't a buzzword bolt-on — it's **load-bearing**, and it's what makes several detectors actually work:
- **Overpricing** — cluster "the same product written five different ways" (a laptop spelled 5 ways) so we can compare like with like.
- **Cloned documents** — catch near-duplicate / copy-paste tender docs (and the one clause slipped in to favor someone).
- **Shell-company / serial-winner** — resolve company-name variants that are really the same entity.
- **Semantic search** — a citizen searches by *meaning*, not exact keywords.

Embeddings are computed in the Python layer at ingest time (a Bulgarian-aware multilingual model) and stored in `pgvector`. _(Open choice: small local embedder vs an embedding API — see `BUILD_PLAN.md`.)_

## What counts as "suspicious" (the detectors)
The heart of it — each one is a known corruption trick:
- **💸 Overpricing** — the same item costs wildly different amounts across deals (a laptop at 10 here, 100 there).
- **🧬 Rigged specs** — requirements so absurdly specific only one pre-chosen company can win.
- **🏆 Serial winners** — the same company (or a cluster of shells sharing an address/owner) keeps winning from the same institution.
- **🚪 Cancelled-after-bids** — a tender opened, then killed once the "wrong" bidder was about to win.
- **🛣️ Impossible scope** — work that physically/financially doesn't add up (repairing a brand-new road).
- **⏰ Late payments** — institutions that chronically pay contractors late.
- **📄 Copy-paste docs** — tender documents near-identical except one clause slipped in to favor someone.

> Build **2–3** of these for the demo, not all 7. Overpricing and serial-winner are usually the most visual and damning.

## What makes it punk
It doesn't politely ask for transparency — it **takes** the data that's already ours and throws the ugly parts in everyone's face. Sharp, a little savage, but **built entirely on facts**: every claim is sourced, nothing is invented. The rebellion is against the corruption, not against the truth.

## The hard boundaries (non-negotiable)
- **Public data only** — no hacking, no logins, no private info.
- **Every flag links to its primary source** — no source, no flag. (On an anti-corruption tool, an unsourced accusation is itself disinformation.)
- **We flag patterns, we don't convict people** — "this looks suspicious, here's the evidence, judge for yourself."

## Who does what (3 lanes)
- **Frontend** — the website people see.
- **Backend** — stores the data and runs the detectors.
- **Scraping** — pulls the data from the government sources.

---

## ⭐ Decision 1 — how wide is the net? (read this first)
The name promises *all* corruption, but "corruption" isn't one dataset — it's dozens (procurement, officials' asset declarations, party financing, public hiring, courts, municipal budgets…). **Each domain = its own scraper + cleanup + detectors.** So:

**A) Narrow — just public procurement** *(recommended to BUILD)*
One domain: who got which contract, for how much, and the tricks used to rig it. Richest, most structured, most provable corruption data in BG; all the detectors work on it; **can actually work on real data in 48h.**
- ✅ Deep, coherent, demonstrably works (the hardest 30% of the score to fake).
- ⚠️ Known space (Bivol/BIRD) → originality must come from the **automation + punk presentation**, not the topic.

**B) Broad — a general "corruption feed" across many domains**
- ✅ Matches the ambitious name; original as a one-stop exposer.
- ⚠️ In 48h this almost always becomes **broad-but-shallow** — many half-working scrapers, nothing convincing. Dangerous given our scope/time weak spot. Judges reward one thing that truly works over five that sort-of do.

**Recommendation: build narrow, pitch broad.** Build *only* procurement for the demo, but the system is already architected so procurement is just **"source #1"** (the scraper contract accepts any source). In the pitch: *"We started with procurement because it's the richest vein — the same engine plugs into asset declarations, party financing, and more. This is the first vertical, not the whole."* Working deep demo **+** big vision. We don't have to lock this now — the code keeps the door open; we're only deciding **where the 48h go.** → procurement.

> **Reconciling with "biggest scope possible":** max scope means **go deep AND wide _within_ procurement** — more sources (TED → data.egov → …), more detectors, semantic/vector features, and prod hardening — **not** spreading across many corruption domains. Breadth *across domains* is what bombs efficiency; breadth *within procurement* is mostly free scope because the rails (scraper contract, ingest, vector DB) are shared. The payoff-per-hour ranking of every piece is in **[`BUILD_PLAN.md`](BUILD_PLAN.md)**.

## Other decisions / things to change
2. **Which 2–3 detectors** to actually build. (Overpricing + serial-winner = most damning + visual.)
3. **The hero example** — one real, named, embarrassing BG case to open the demo with. _What is it?_
4. **The name** — `corruption-fucker` is the repo name; is that the public/pitch name, or a Bulgarian one (ТЪРГ / РЕНТГЕН / …)?
5. **Data reality check** — the juiciest data (company owners) is partly paywalled; easiest real sources are **TED + data.egov.bg**. Scope the demo to what's actually reachable in 48h.
6. **License** — currently GPL-3.0; the original scaffold suggested MIT. Pick one before the demo (see `MERGE_REPORT.md`).

---
_Status: draft for team review. Once agreed, the one-paragraph version goes at the top of `README.md`._
