# Fact-Check Verdicts — Read This Before the Meeting

> Worst-best-critic mode. Every idea tested against **real data** (I hit the actual endpoints) and **real premises** (sourced fact-check). Nothing here is vibes. Verdicts: ✅ build-ready · ⚠️ viable only if you change X · ❌ cut it.
>
> **Cyrillic note (your requirement):** both front-runner datasets — **CIK elections and СЕМ media register — are clean UTF-8**, verified by decoding real rows. The windows-1251 mojibake risk applies to *older* gov sites; keep the `chardet → decode → UTF-8` discipline as a guard, but the data you'll most likely use is safe.

---

## Live data recon — what actually works (tested June 3)

| Source | Test result | Verdict |
|---|---|---|
| **CIK elections** (`results.cik.bg/<code>/export.zip`) | ✅ 17MB zip downloads with a **browser User-Agent**; unzips to `sections`, `votes`, `parties`, `preferences`, `protocols`; **semicolon-delimited, UTF-8**, section-level, EKATTE-keyed. No join, no paywall. | **Strongest open dataset in BG. Build-ready.** |
| **СЕМ media register** (`cem.bg/linear_reg.php`) | ✅ UTF-8; paginated (`?cat=0&page=1..14`); detail pages (`linear_reg_docs.php?id=`) have **ЕИК + name + address**. ⚠️ **No beneficial-owner field** on the page. | Identity layer ✅, ownership layer ❌ (needs Trade Register). |
| **Trade Register** (beneficial owners) | ❌ No free bulk/API; per-record + CAPTCHA; bulk is **paid**. | Ownership join is the wall. Curate for demo. |
| **data.egov.bg API** | ❌ `/api/datasetList` → 404 "Непознат метод". Documented method names are wrong/changed. | Don't trust the API blind; confirm on-site. |
| **Parliament API** (`parliament.bg`) | ✅ page loads, UTF-8 | Data fine, but **idea is derivative** (see #6). |

---

## Verdict per idea

### #3 АНОМАЛИЯ — Election forensics  →  ✅ **BUILD-READY (top pick on data)**
- **Data:** ✅ fully confirmed live. CIK `export.zip`, clean UTF-8, section-level, self-contained, no paywall, no join. The cleanest path to a working demo on real data (Technical 30% loves this).
- **The one fix that saves it from a journalist juror:** ❌ **do NOT lead with Benford's Law** — it's contested-to-discredited for elections (Deckert–Myagkov–Ordeshook 2011). ✅ **Lead with the Klimek "election fingerprint"** (turnout × winner-share 2D distribution, PNAS 2012) + a **last-digit uniformity test**. Frame every output as *"anomaly warranting investigation, not proof of fraud."* Benford can appear as one minor, caveated panel.
- **Punk angle intact:** *"These 47 stations are a statistical miracle."* Rigor IS the punk.
- **Risk:** method defensibility (handled by the fix above) + don't over-claim fraud.

### #4 РЕНТГЕН — Person X-ray extension  →  ✅ **VIABLE (best form-factor + originality combo)**
- **Data:** ✅ light and curated. The "is this person a sanctioned oligarch?" core runs on the **Magnitsky list (8 names, verified)** + court cases + procurement — all curated public record, no paywall, no scraping marathon.
- **Why it survives where the auto-graph didn't:** you don't need the full Trade Register; you hand-curate the ~8 sanctioned figures + top public names for the demo. Small, perfect, hover-the-name demo > flaky universal one.
- **Drop the "fake-news detection" half** — unreliable, a Q&A deathtrap, and self-defeating (if wrong, *you're* the disinformer). Keep the **outlet-ownership transparency** overlay (curated) + person dossier.
- **Originality:** ✅ no dominant consumer tool does "hover a name → sanction/court/contract dossier" as a browser extension.
- **Risk:** Cyrillic name-matching on the page (declensions); scope to a curated entity list.

### Media-Ownership Transparency (was inside #1)  →  ⚠️ **VIABLE WITH A CAVEAT (best originality gap)**
- **Why it's tempting:** the fact-check found this is the **least-crowded, premise-sound lane** — every media-freedom report complains there's *no single unified "who owns which media" tool* and the data is "inconsistent, outdated, contradictory."
- **The caveat (tested):** ✅ СЕМ gives you media **identity + ЕИК** cleanly, but ❌ the **beneficial-owner layer is paywalled** (Trade Register). So a *fully-automatic* ownership graph is **not** a clean 48h win.
- **How to make it work:** (a) **curate** ownership for ~20–30 major outlets (publicly documented) and present a unified graph; or (b) make the *meta-story* the product — *"here are 3 official registers that don't agree with each other,"* visualizing the contradictions. The messiness IS the exposé.
- **Risk:** if you promise "all media, automatically," you'll miss. Promise a curated, sourced map.

### #2 ТИШИНА — ЗДОИ silence scoreboard  →  ⚠️ **VIABLE ONLY REFRAMED**
- **The flaw I missed before:** the legal ЗДОИ deadline is **14 days** (чл. 28 ЗДОИ), extendable to ~28. **You cannot demonstrate a missed deadline live in a 48h hackathon.** The original "live stopwatch" mechanic is impossible.
- **The save:** ✅ build on **existing data** — the Access to Information Programme (ПДИ / aip-bg.org) runs an annual **"Civic Audit of Active Transparency"** (94 indicators, fresh **2026** edition). Rank chronically non-compliant institutions from *historical* data; pitch as a monitoring scoreboard, not a live timer.
- **Originality caveat:** ПДИ already publishes a rating → differentiate on UX, cross-dataset linking, or auto-rechecking, or a juror says *"ПДИ already does this."*

### #5 ПОГРЕБАНО В ПЕТЪК — "Buried on Friday" gazette  →  ❌ **CUT (premise is false)**
- The State Gazette publishes **every Tuesday and Friday by law** (Закон за "Държавен вестник"). Friday is a *scheduled* day — ~half of all laws come out Fridays. "Published on Friday" detects **nothing**.
- **Only if someone insists:** pivot the signal to **извънредни броеве** (extraordinary issues), which are off-schedule and require the Speaker's sign-off — their *timing* is meaningful. But that's a narrower, harder build (JSF + PDF). Recommend cutting.

### #6 ОТСЪСТВАЩИТЕ — MP attendance/vote tracker  →  ❌ **CUT (derivative)**
- Saturated: **Отворен Парламент / Open Parliament**, **Стража / Strazha** (forced the NS to a 24h roll-call deadline), and **yurukov** open data all do this and are live in 2026. The reglament explicitly penalizes "thousand-times-chewed concepts." Don't build another.

### #1 ПАРАЗИТ — Procurement→media→power graph  →  ❌ **CUT as conceived (derivative + paywalled join)**
- **Procurement transparency is owned** by BIRD (`bird.bg/contracts`, `scan.bird.bg`) and Bivol — textbook derivative pitch.
- **The ownership join is paywalled** (Trade Register). The two things that made it shine are both blocked.
- **What's salvageable:** the *media-ownership* sub-graph (see above), re-centered and curated. The procurement spine should go.

### #7 АНАЛОГ — "no-AI punk statement"  →  ✅ **KEEP as framing, not a standalone**
- Strong as the *medium/voice* of whichever idea wins (esp. the extension or the scoreboard). Pair it with real data so it's substance + statement, not aesthetic alone.

---

## Re-ranked for tomorrow (by VERIFIED feasibility × punch × originality)

| Rank | Direction | Verdict | Why it's here |
|---|---|---|---|
| **1** | **АНОМАЛИЯ** — election forensics (Klimek, not Benford) | ✅ | Only idea with **fully-confirmed open data**, self-contained, max Technical score. Fix the method and it's the safest *strong* demo. |
| **2** | **РЕНТГЕН** — sanctions-aware person X-ray extension | ✅ | Novel form factor, curated data (no paywall), punk as hell, your idea. |
| **3** | **Media-ownership map** (curated/meta-story) | ⚠️ | Best originality gap, but ownership data is paywalled → curate or tell the "registers disagree" story. |
| 4 | **ТИШИНА** — reframed on ПДИ audit data | ⚠️ | Citizen-actionable, but needs historical-data reframe + differentiation. |
| — | Gazette / MP tracker / procurement graph | ❌ | Cut: false premise / derivative / paywalled. |

**My blunt recommendation for the meeting:** go in with **#1 (АНОМАЛИЯ) and #2 (РЕНТГЕН)** as the two real candidates, with **#3 (media-ownership)** as the wildcard if the team is drawn to it and accepts the "curated, not automatic" scope. Decide *after* the Friday tracks drop — but pre-scaffold the shared Python+DuckDB+React spine either way. All three share it.

---

## 🔁 RE-WEIGHTING (added Jun 4): "recurring problem an ordinary citizen hits often"

A key criterion surfaced: the rubric's UX 20% asks *"can an ordinary citizen use it?"* and the radical-thinking 30% rewards provoking *citizen* reaction. So **weight ideas by how recurring + everyday-citizen-facing they are.** This re-ranks the survivors:

| New rank | Idea | Recurring? | Note |
|---|---|---|---|
| 🥇 | **РЕНТГЕН — news X-ray extension** (+ outlet-ownership) | **Daily** (every article) | Ordinary citizen, everyday use, curated low-risk data, novel form factor. **New front-runner.** |
| 🥈 | **Media-ownership "who owns what you read"** | Daily | Merge into the X-ray |
| 🥉 | **ТИШИНА** (reframed on ПДИ data) | Recurring bureaucracy | Citizen-actionable |
| ⬇️ | **АНОМАЛИЯ** (elections) | Once per cycle | **Backup** — only if the Friday track is election-themed. POC done (`14_anomaly_poc.md`), so it's a ready fallback. |

**Synthesis = the strongest recurring-problem product:** a browser extension that, as you read BG news daily, shows **(a) who owns this outlet** (СЕМ + curated) and **(b)** whether named people are **sanctioned/compromised** (Magnitsky list + court/procurement), every claim sourced + clickable. Daily-use, ordinary-citizen, info-democracy, punk. *(Data-validate this before fully committing — see post-meeting plan.)*

> ⚠️ **Everything above assumes the June 5 tracks don't force a specific topic.** Treat this as your *default* direction; the announced challenges may add a track-specific prize that changes the math. The mini-plans in `08_master_plan.md` are built to pivot.
