# Data Cheat-Sheet — Real Schemas (pre-explored)

> You flagged **data wrangling** as a weak spot — so here's the actual structure of the two confirmed datasets, pulled from the live data + official docs, so on Friday you skip the discovery pain. **Pre-downloading these public datasets is allowed** (the reglament permits "existing datasets"). Do it tonight.
>
> ✅ Both datasets verified **UTF-8** (clean Cyrillic, no windows-1251). ✅ Both **semicolon-delimited** (`;`).

---

## A. CIK Election Data (the anomaly idea — data fully confirmed)

### Get it
```bash
UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36"
curl -s -A "$UA" "https://results.cik.bg/pi2021/export.zip" -o cik.zip   # browser UA REQUIRED (WAF)
unzip cik.zip -d cik_data
```
- URL pattern: `results.cik.bg/<election-code>/export.zip` (e.g. `pi2021` = parliamentary 2021). Each election has its own code — find it from the election's `csv.html` page.
- ⚠️ **Must send a browser User-Agent** or the WAF returns 404.

### Files (parliamentary 2021 = real sizes)
| File | Rows | What |
|---|---|---|
| `sections.txt` | 12,941 | One row per polling section (the spine) |
| `protocols.txt` | 12,941 | Section protocol = **turnout numbers** (voters, ballots, valid/invalid) |
| `votes.txt` | 12,941 | Valid votes **per party** per section |
| `cik_parties.txt` | 29 | Party number → name |
| `preferences.txt` | 3.9M | Preferential votes per candidate (huge — query with DuckDB, don't load to memory) |
| `votes_mv.txt` / `preferences_mv.txt` | — | Machine-vote breakdowns |

### Exact columns (semicolon-delimited)
**`sections.txt`** — the join spine:
```
1) section_code   (9 digits: region(2)+municipality(2)+admin(2)+section(3))
2) admin_unit_id
3) admin_unit_name      e.g. "01. БЛАГОЕВГРАД"
4) ekatte                ← settlement code, joins to NSI/geography
5) settlement_name       e.g. "гр.Банско"
6) is_mobile (0/1)   7) is_ship (0/1)   8) is_machine_vote (0/1)
```
Sample: `010100001;1;01. БЛАГОЕВГРАД;2676;гр.Банско;0;0;1`

**`votes.txt`** — variable-length repeating groups:
```
1) section_code   2) admin_unit_id
then repeating per party:  party_no ; valid_votes ; votes_from_ballots ; votes_from_machine
```
So columns 3+ are groups of 4. Parse by stepping in 4s after the first 2 fields.

**`protocols.txt`** — turnout, but ⚠️ **field positions depend on form type**:
```
1) form_no   ← 1, 7, 8, or 14; THIS determines the layout
2) section_code   3) admin_unit_id   4) page_serials (|-separated)
then numbered protocol fields...
```
Key fields you need for turnout (Form #8, the common machine+paper form):
- voters in list (point 1), voters who voted / signatures (point 2), valid votes total (point 6).
- **Gotcha:** Forms 1/7/8/14 put these at different positions. Build a small per-form column map; don't assume one layout. *(This is the main wrangling task — budget for it.)*

### Computing the Klimek "election fingerprint" (per section)
- `turnout = voters_who_voted / voters_in_list`  (from protocols)
- `winner_share = max(party valid_votes) / sum(valid_votes)`  (from votes)
- Plot the 2D distribution of (turnout, winner_share) across all sections → clusters at high-turnout+high-winner-share = the fraud signal.
- **Last-digit test:** take last digit of `valid_votes` per party-section → should be ~uniform; spikes at 0/5 = fabrication signal.
- Join everything on `section_code`. Bring in `ekatte` for the map.

### DuckDB load (zero-import, reads the txt directly)
```sql
CREATE TABLE sections AS SELECT * FROM read_csv('cik_data/sections_*.txt',
  delim=';', header=false, names=['section_code','admin_id','admin_name','ekatte','settlement','is_mobile','is_ship','is_machine']);
-- votes/protocols: read raw then reshape in SQL or pandas (variable-width rows)
```

---

## B. СЕМ Media Register (the media-ownership / X-ray idea)

### Structure (verified)
- Base list: `cem.bg/linear_reg.php?cat=0&page=1..14` (paginated, ~14 pages).
- Detail page: `cem.bg/linear_reg_docs.php?id=<N>&cat=0` and `company_reg_docs.php?id=<N>`.
- ✅ UTF-8. ✅ Detail pages contain **ЕИК** (company ID), name, address.
- ❌ **No beneficial-owner field** on the page. To get who *owns* it → join ЕИК to the Trade Register (paywalled) **or curate** for your demo set.

### Realistic plan
1. Scrape the list → get all outlet IDs + names + ЕИК (httpx + BeautifulSoup; it's static PHP).
2. For ~20–30 major outlets, **curate ownership by hand** from public record (you can't auto-get owners free).
3. Flag any owner on the Magnitsky list (below).

---

## C. Magnitsky list (ready reference — Tier-1 "safe to name")
US/UK Global Magnitsky sanctions for corruption — public record, cite freely:

| Year | Name | Note |
|---|---|---|
| 2021 | **Delyan Peevski** | media mogul / ex-MP (contesting in US court → say "sanctioned," not "convicted") |
| 2021 | **Vassil Bozhkov** (Bojkov) | businessman/oligarch |
| 2021 | **Ilko Zhelyazkov** | ex-deputy chief, State Agency for Technical Operations |
| 2023 | **Vladislav Goranov** | ex-finance minister (GERB) |
| 2023 | **Rumen Ovcharov** | ex-energy minister (BSP) |
| 2023 | **Nikolay Malinov** | pro-Russia lobby leader (charged w/ espionage) |
| 2023 | **Aleksandar Nikolov** | ex-head, Kozloduy NPP |
| 2023 | **Ivan Genov** | ex-head, Kozloduy NPP |

---

## Universal wrangling rules (your weak spot → drill these)
- **Always semicolon-delimited, UTF-8** for these two sources. For *other* gov sites: bytes → `chardet` → decode (`cp1251` if legacy) → UTF-8.
- **DuckDB reads CSV/txt directly** — no import step. Use it for anything over ~100k rows (the preferences file is 3.9M).
- **Variable-width rows** (votes/protocols) — parse in Python (step through fields), then write a clean table to DuckDB.
- **Join key discipline:** `section_code` (elections), `ekatte` (geography), `eik` (companies). One name everywhere (see `10_master_rules.md` glossary).
- **Validate before trusting:** print 5 real rows, eyeball the Cyrillic, check row counts match across files (sections = votes = protocols = 12,941).
