# Bulgarian Open-Data Sources — Field Guide (verified June 2026)

> Scout report for LiberHack. Targets the three domains we chose: **Money & procurement · Info & democracy · Justice & rights.** Every URL verified live.
>
> **Bottom line:** BG accountability data splits into **"genuinely open"** (CIK elections, data.egov.bg API, Parliament API, NSI) vs **"PDF/scrape-only fortress"** (asset declarations, commercial register, court acts, State Gazette). **That gap is itself an exposé angle.** The proven blueprint is Bivol/BIRD's "join-the-registries" method.
>
> ⚠️ **3 corrections to common assumptions:** the audit office is **bulnao.government.bg** (not smetna-palata.bg); **CIK and ИСУН WAF-block bots** (use a real browser User-Agent / Playwright); the **commercial register has no free bulk/API** (paid or per-record scrape only).

---

## ⭐ TOP PICKS for a 48-hour build (ranked by ease × punch)

| # | Source | Ease | Punch | Why |
|---|--------|------|-------|-----|
| **1** | **CIK election results** (results.cik.bg `/opendata/` CSVs) | ★★★★★ real CSV, EKATTE keys | ★★★★★ | Section-level anomaly detection. Cleanest open data in BG. *(Browser UA — WAF 403s bots.)* |
| **2** | **data.egov.bg API** (CKAN JSON: `/api/datasetList` + `/api/resourceData`) | ★★★★☆ documented API | ★★★★☆ | One endpoint, many datasets incl. procurement CSVs. Bonus: a "stale open data" exposé writes itself. |
| **3** | **ЦАИС ЕОП procurement CSVs** (via data.egov.bg `caiseop_contracts*.csv`) | ★★★★☆ bulk CSV | ★★★★★ | "Serial winners" — highest-impact corruption dataset, proven by Bivol. |
| **4** | **Parliament API** + yurukov repo | ★★★★☆ official JSON + ready scraper | ★★★★☆ | Attendance/votes/penalties leaderboard. Fork yurukov to skip messy-XML pain. |
| **5** | **NSI / Infostat** (nsi.bg/opendata) | ★★★★☆ downloadable | ★★★☆☆ | The denominator that makes every dataset hit harder (per-capita, per-region). |
| **6** | **СЕМ media-ownership** (cem.bg, enumerable IDs) | ★★★☆☆ scrape PHP | ★★★★☆ | Ownership-concentration graph; joins to register + money. |
| **7** | **ИСУН 2020 EU-funds** (2020.eufunds.bg) | ★★☆☆☆ WAF, no IDs | ★★★★★ | Huge punch (€300M corrections) but needs Playwright + fuzzy-matching. |

**Skip-for-48h (high friction):** Commercial register (paid/ToS-grey), asset declarations (PDF/OCR/GDPR), court acts (redacted/fragmented), State Gazette (JSF+PDF), lex.bg (restrictive ToS).

**The strongest single build:** combine **#1 + #3 + #6** into one graph — *who wins the public money, who owns the media that protects them, and where the votes come in suspiciously clean.* Bivol's method, made into a live tool.

---

## Domain 1 — MONEY & PROCUREMENT

### 1.1 data.egov.bg — National Open Data Portal
- **URL:** https://data.egov.bg · API doc: https://data.egov.bg/document/view/1 · code: https://github.com/governmentbg/data-gov-bg (EUPL)
- **Holds:** Central hub — datasets from hundreds of agencies incl. ЦАИС ЕОП procurement dumps (`caiseop_contracts2020.csv`), ministry spending, NSI classifications, election results.
- **Access:** **Real REST API** (CKAN fork): `/api/datasetList`, `/api/resourceData` → JSON; read is open, some writes need a key. Direct CSV per resource.
- **Gotchas:** Uneven quality, many stale datasets; mostly UTF-8 but watch Windows-1251 in older CSVs; coded columns (EU-funding `1/0`, VAT `0` = ex-VAT).
- **Legality:** Explicitly open re-use (EU Directive 2019/1024 / PSI). **Cleanest legal footing here.**
- **Punk angle:** A **"staleness dashboard"** — rank ministries by how out-of-date their legally-mandated open data is. The transparency portal is itself full of abandoned datasets.

### 1.2 ЦАИС ЕОП / app.eop.bg + АОП — Public Procurement
- **URL:** https://app.eop.bg · search: https://app.eop.bg/today/reporting/search · agency: https://www2.aop.bg
- **Holds:** Every tender, decision, contract, framework agreement since the platform became mandatory. Values, authority, winner, EU-funding flag.
- **Access:** No clean public REST API. Two real routes: **(a) bulk CSV on data.egov.bg** (`caiseop_contracts*.csv` — the easy path); (b) on-platform search UI (scrapeable; doc IDs cross-link to the CSVs).
- **Gotchas:** CSVs per-year/per-authority (stitching needed); coded numeric columns; `app.eop.bg` UI is heavy/JS.
- **Legality:** Public-record; CSVs published as open data. Safe.
- **Punk angle:** **"Serial winners."** The exact dataset Bivol used to surface a Peevski-linked firm as a top procurement winner → cancelled Hemus tender. Rank winners by € won; flag firms that only win from one authority, or registered suspiciously close to their first win.

### 1.3 ИСУН 2020 / 2020.eufunds.bg — EU Funds beneficiaries
- **URL:** https://2020.eufunds.bg/bg/0/0/Beneficiary · portal: https://www.eufunds.bg
- **Holds:** Every EU-funded project beneficiary, grant amount, operational programme, contractor.
- **Access:** **HTML scraping only, behind a WAF that 403s non-browser requests** — needs Playwright/real browser. No API.
- **Gotchas:** 2007–2013 registries have **no unique company IDs + misspelled names** → fuzzy matching to join to the company register. WAF blocks naive scrapers.
- **Legality:** Public transparency data; EU-level data also at the Commission's Financial Transparency System.
- **Punk angle:** EU money + missing IDs = laundering-by-typo. BIRD found OP "Innovation & Competitiveness" flagged 650/713 contracts for ~BGN 300M corrections. Cross-ref beneficiaries ↔ procurement winners ↔ company register.

### 1.4 Търговски регистър / Commercial Register
- **URL:** https://portal.registryagency.bg · BULSTAT: https://www.bulstat.bg
- **Holds:** All companies, owners, **beneficial owners (UBO, под ЗМИП)**, management, filings.
- **Access:** Free **per-record web search** (no login). **No free bulk/API** — full DB free only to state bodies; paid for everyone else. Commercial proxy: APIS (paid).
- **Gotchas:** Per-record scraping is ToS-grey; CAPTCHAs likely. **Use ЕИК/UIC as the universal join key.**
- **Legality:** **Most restrictive here** — public to read per-record; systematic bulk scraping likely breaches ToS.
- **Punk angle:** The *absence* of free bulk access is the meta-story: Bulgaria charges citizens for the company data that fights corruption.

### 1.5 Сметна палата / Audit Office — Asset declarations
- **URL:** https://www.bulnao.government.bg/bg/publichen-registr/ *(NOT smetna-palata.bg)*
- **Holds:** Имущество и интереси declarations — vehicles, cash >€5k, bank holdings, foreign assets, conflicts of interest. Since **14 Feb 2026** the audit office absorbed the old anti-corruption commission's vetting role. Filing deadline 15 May 2026 → 2026 declarations are fresh.
- **Access:** **Web register + PDF only. No API, no bulk.**
- **Gotchas:** Scanned/PDF, free-text, no IDs → OCR + entity-resolution. 2026 form schema differs from history.
- **Legality:** Public register, free to view. Republishing personal finance carries GDPR sensitivity — stick to public-office holders.
- **Punk angle:** **"Declared vs reality"** — cross-ref declared assets against property/cars/company holdings. Richest BG exposés live here precisely because the data is un-queryable.

---

## Domain 2 — INFO & DEMOCRACY

### 2.1 Народно събрание / Parliament — Official API
- **URL:** API: https://www.parliament.bg/pub/api.html · site: https://www.parliament.bg
- **Holds:** MPs by assembly, profiles, leadership, districts, **absences (отсъствия)**, **penalties (наказания)**, sessions, (no-)confidence votes, parliamentary control, procurement.
- **Access:** **Official JSON API** — `GET /api/v1/eu-options`, `GET /api/v1/eu-dossier-type`, `POST /api/v1/fn-dossier`; menu exposes absence/penalty/confidence endpoints (enumerate from the page).
- **Gotchas:** Underlying data "not indexed, often not valid XML." Roll-call individual votes are the hardest to get cleanly.
- **Companion:** **yurukov/Bulgarian-Parliament-Open-Data** (https://github.com/yurukov/Bulgarian-Parliament-Open-Data, mirror parliament.yurukov.net) — scraper that already downloads MP votes per topic, absences, bills. Inactive, **no explicit license (ask before redistributing)**, may lag the 52nd Assembly — but it maps where the data lives.
- **Punk angle:** **AzDaglasuvam-style** no-show / vote-flip leaderboard. Absences + penalties endpoints make a shame board trivial.

### 2.2 ЦИК / results.cik.bg — Election results
- **URL:** Hub https://results.cik.bg · e.g. https://results.cik.bg/pe202604/opendata/index.html · 2021 https://results.cik.bg/pi2021/csv.html
- **Holds:** Results down to **section (СИК) level** — turnout, votes per party/candidate, preferential votes, abroad votes, station locations.
- **Access:** **Real bulk CSV + spreadsheets** under PSI Directive. Predictable URLs `results.cik.bg/<code>/opendata/`. Keyed by **EKATTE** → joins to geography/demographics.
- **Gotchas:** **WAFs/403s bots** (browser UA). Each election has a different URL code. Tool: **aandr/bgelections** (https://github.com/aandr/bgelections).
- **Legality:** Explicitly open data. **Cleanest, richest dataset in this report.**
- **Punk angle:** Section-level + EKATTE = **anomaly detection.** Hunt improbable single-station results, controlled-vote clusters, preferential-vote irregularities. The single most hackathon-ready accountability dataset in Bulgaria.

### 2.3 Държавен вестник / State Gazette
- **URL:** https://dv.parliament.bg/DVWeb/broeveList.faces
- **Holds:** Official journal — every law, decree, ministry/municipality order, twice weekly (Tue/Fri).
- **Access:** **PDF only** via clunky JSF app. No XML/API. Alt: **N-Lex BG** (https://n-lex.europa.eu/n-lex/legis_bg/apis_form) for ELI search.
- **Gotchas:** Stateful JSF navigation; PDF → OCR/extract.
- **Punk angle:** **"Buried on Friday"** — analyze publishing timing/volume: what drops right before holidays? Diff-track silent legal changes.

### 2.4 СЕМ / cem.bg — Media-ownership register
- **URL:** Register https://www.cem.bg/linear_reg.php · ownership https://www.cem.bg/infobg/33
- **Holds:** TV/radio/on-demand/distribution providers — boards, capital owners, **beneficial owners**, links to commercial register (e.g. bTV → CME Bulgaria BV / NL).
- **Access:** **HTML scraping only** (old PHP, enumerable query-string IDs). No API/bulk.
- **Punk angle:** **Media-ownership concentration graph** — cross-ref СЕМ UBOs ↔ commercial register ↔ procurement/EU-funds to show which media are owned by people feeding at the public trough.

### 2.5 НСИ / nsi.bg — National Statistical Institute
- **URL:** Open data https://www.nsi.bg/opendata/ · Infostat https://infostat.nsi.bg
- **Holds:** Demographics, economy, labour, inflation, **police-recorded crime**, regional breakdowns, NUTS/EKATTE.
- **Access:** Open data portal + Infostat query system; mirrored on data.egov.bg. (Probe Infostat's XHR calls for a de-facto API.)
- **Legality:** NSI licence (attribute; not pure public-domain) + Eurostat copyright on EU series.
- **Punk angle:** The **denominator** for everything — normalize spending/crime/EU-funds per capita per region to expose where money flows vs where need is.

---

## Domain 3 — JUSTICE & RIGHTS

### 3.1 Court decisions — legalacts / sac / portal.justice.bg
- **URL:** https://legalacts.justice.bg · SAC https://sac.justice.bg (reports https://sac.justice.bg/pages/bg/reports) · e-justice https://portal.justice.bg
- **Holds:** Published court acts, searchable by case/act type; SAC reviews acts of the government, ministers, SJC, BNB.
- **Access:** **HTML search portals, no bulk/API.** Acts are **personal-data-redacted**, no login.
- **Gotchas:** Redaction (some criminal acts unpublished or "without reasoning"); no stable cross-court IDs; fragmented per-court sites.
- **Legality:** Public but redacted — **don't de-anonymize.**
- **Punk angle:** Sentencing/outcome disparities across judges/districts; track high-profile cases quietly published "without reasoning."

### 3.2 Crime & prosecution stats — NSI + prb.bg + МВР
- **URL:** Crime https://www.nsi.bg/en/statistical-data/154/494 · outcomes https://www.nsi.bg/en/statistical-data/160/515 · Prosecution https://www.prb.bg · regional https://www.regionalprofiles.bg
- **Holds:** Police-recorded crime by region/outcome; prosecution annual reports.
- **Access:** **NSI tables (downloadable)** = realistic open route. prb.bg/МВР = annual-report PDFs.
- **Gotchas:** **Methodological fragmentation** — Prosecution, MoJ, МВР keep separate stats on different indicators (a citable problem). Solve-rates vary wildly by district.
- **Punk angle:** **"The numbers don't add up"** — МВР-recorded vs prosecuted vs convicted side by side; the institutions can't even agree how to count.

### 3.3 ЗДОИ / Access to Information
- **What:** Закон за достъп до обществена информация (FOIA). Lead NGO: **Access to Information Programme** (aip-bg.org). Per-institution logs, no central API.
- **Punk angle:** A tool that **auto-drafts ЗДОИ requests** and **tracks which institutions miss the legal deadline** — bureaucratic silence becomes a public scoreboard. (You *generate* the data by filing.)

### 3.4 Consumer / tenant rights — pravatami.bg, lex.bg
- **URL:** Rights https://www.pravatami.bg · Legislation https://lex.bg
- **Access/Legality:** **lex.bg is private (ЛЕКС.БГ ЕАД) with restrictive ToS — scraping full texts likely breaches terms** (link + cite titles, don't reproduce). Consolidated law has no clean public API; commercial systems (Apis, Ciela, Lakorda) are paywalled.
- **Punk angle:** Primary law is technically public yet practically locked behind private databases — a tool that surfaces the *actual applicable statute* for a tenant/consumer dispute fills a real gap.

---

## Civic-tech precedent (proven feasible)
- **Bivol.bg / BIRD.bg** (https://bird.bg) — since 2015 join commercial register + procurement + asset declarations + EU-funds to expose serial winners, GPGate, Hemus. **Your blueprint.**
- **yurukov** (parliament) and **aandr/bgelections** (elections) — ready scrapers to fork.
- **Open Contracting Partnership** — documented BG procurement-scraping methodology.

---

### Sources
[data.egov.bg](https://data.egov.bg) · [API doc](https://data.egov.bg/document/view/1) · [data-gov-bg repo](https://github.com/governmentbg/data-gov-bg) · [app.eop.bg](https://app.eop.bg) · [aop.bg](https://www2.aop.bg) · [parliament API](https://www.parliament.bg/pub/api.html) · [yurukov repo](https://github.com/yurukov/Bulgarian-Parliament-Open-Data) · [results.cik.bg](https://results.cik.bg) · [aandr/bgelections](https://github.com/aandr/bgelections) · [dv.parliament.bg](https://dv.parliament.bg/DVWeb/broeveList.faces) · [N-Lex BG](https://n-lex.europa.eu/n-lex/legis_bg/apis_form) · [registryagency.bg](https://portal.registryagency.bg) · [bulnao](https://www.bulnao.government.bg/bg/publichen-registr/) · [2020.eufunds.bg](https://2020.eufunds.bg) · [cem.bg](https://www.cem.bg/linear_reg.php) · [nsi.bg open data](https://www.nsi.bg/opendata/) · [Infostat](https://infostat.nsi.bg) · [legalacts.justice.bg](https://legalacts.justice.bg) · [sac.justice.bg](https://sac.justice.bg) · [portal.justice.bg](https://portal.justice.bg) · [lex.bg](https://lex.bg) · [BIRD](https://bird.bg) · [Open Contracting Partnership](https://www.open-contracting.org/2018/10/21/how-journalists-in-bulgaria-are-using-data-to-investigate-abuse-of-eu-funds-in-procurement/)
