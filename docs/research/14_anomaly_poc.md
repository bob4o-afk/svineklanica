# Anomaly POC — Real Results on 2021 CIK Data

> I ran the Klimek "election fingerprint" + last-digit test on the **actual** Bulgaria 2021 parliamentary data (pre-downloaded, public). This proves idea **АНОМАЛИЯ** end-to-end **before** you commit. Chart: `14_fingerprint.png`.
>
> ⚠️ This is a **research POC to inform the decision** — the actual product gets built on-site Friday (originality rule). Treat the numbers + caveats as your confidence + your Q&A armor.

---

## What the pipeline did (in seconds)
- Joined **12,938 sections** across `sections` + `protocols` + `votes` on `section_code`.
- Computed per section: **turnout** = voted/registered (protocols), **winner share** = national-winner votes / total valid (votes).
- National winner = **party #28** (GERB-SDS), ~27% mean share — matches the real 2021 result. ✅ pipeline is correct.

## The signal (real, defensible)
- **Klimek fingerprint:** **78 domestic sections (0.63%)** sit in the ballot-stuffing corner — **turnout ≥ 90% AND winner-share ≥ 60%**. After splitting abroad out: **0 abroad** in that corner.
- Top examples (turnout / winner% / settlement):
  - 100% / 100% — гр.Белово (13 votes), гр.Долна баня (28)
  - 100% / 96% — гр.Брусарци · 100% / 92% — с.Балей, гр.Долни чифлик · 100% / 91% — гр.Сливен (105)
- These are the *"too clean to be real"* stations — exactly the punch line: **"We didn't pick these. The math did."**

## The honest caveats (THIS is what wins the jury — rigor IS the punk)
1. **Abroad sections are a data artifact, not fraud.** 504 abroad sections show ~100% "turnout" because there's **no fixed electoral roll abroad** (denominator ≈ people who showed up). A naive tool flags them falsely — **we exclude them, and we say so on stage.** Catching this yourself disarms the data-journalist juror.
2. **Last-digit test came back CLEAN** (digits 9.5–10.7%, ~uniform). So there is **no strong fabrication signal** by that test nationally. **Report this honestly** — "we checked, it's clean" builds more credibility than crying fraud everywhere.
3. **Flagged sections are tiny** (13–200 votes) — small-N noise is real. So the framing is **"flag for investigation," never "proof of fraud."**

> The defensible story: *"The fingerprint surfaces 78 domestic sections worth a journalist's attention. We excluded abroad artifacts and ran a last-digit check that came back clean — so we're handing you leads, not verdicts."* That sentence is jury-proof.

## Why this matters for tomorrow's meeting
- ✅ **Data pipeline is trivial** (semicolon UTF-8, joins in seconds) → low execution risk.
- ✅ **A real, visual, striking signal exists** → strong demo punch + you have the chart already.
- ✅ **The team demonstrably understands the caveats** → maxes "Radical Critical Thinking" + survives Q&A.
- **Verdict reinforced:** АНОМАЛИЯ is the **confident pick** if nothing at the opening forces otherwise.

## The parsing recipe (so Friday's rebuild is fast)
- `sections`: `;`-split → `section_code, admin_id, admin_name, ekatte, settlement, mobile, ship, machine`.
- `protocols`: `;`-split → turnout = `int(f[6]) / int(f[5])` (voted/registered; valid for forms 1/7/8). Guard `0 < voted ≤ registered`.
- `votes`: first 2 fields = section, admin; then **groups of 4** = `party_no, valid, ballots, machine`. Sum valid, winner = national top party's valid / total.
- **Exclude abroad**: `section_code` starts `32` or settlement contains a comma (country).
- Use **DuckDB** for the join; reshape variable-width `votes` rows in Python first.

*(The throwaway POC script lives in my session — rebuild it clean on-site; it's ~40 lines.)*
