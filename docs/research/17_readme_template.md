# Submission README Template — Fill In, Submit in 5 Min

> Copy this into the repo's `README.md` and fill the `[brackets]` on Sunday morning. A clean README + repo hygiene is part of the Technical score (it proves the project is real) and makes judging frictionless. Keep it punchy — it's a pitch, not docs.

---

```markdown
# [PROJECT NAME] — [one-line punk tagline in Bulgarian]

> [The wound, one sentence. The same cold-open line from your pitch.]

**LiberHack 2026 · [track] · Team [name]**

## Проблемът / The Problem
[2–3 sentences: the systemic, recurring Bulgarian problem. Name it plainly. Why it's broken.]

## Какво прави / What it does
[2–3 sentences: what the tool does + the damning thing it surfaces. The everyday-citizen use.]

[★ 1 screenshot/GIF of the damning output — the "punch". Drop the image in /docs and link it.]
![demo](docs/demo.png)

## Реални данни / Real data sources
All public, legal, open re-use:
- [Source 1 — name + URL] — [what we use]
- [Source 2 — name + URL] — [what we use]
[Every claim in the tool links back to one of these.]

## Как работи / How it works
- **Frontend:** Vite + React + TypeScript [+ extension via WXT, if applicable]
- **Backend/Data:** Python + FastAPI + DuckDB
- **Method:** [1 line — e.g. "scrape → normalize Cyrillic → join on ЕИК → flag"]
- Runs **locally**, no cloud required.

## Run it
```bash
# data
cd data && uv sync && uv run [ingest script]
# api
cd ../api && uv sync && uv run uvicorn main:app
# web
cd ../web && pnpm install && pnpm dev
```
[If it's an extension: how to load the unpacked build.]

## Каузата / Why it matters
[1–2 sentences. What a citizen/journalist does with it. The call to action.]

## Honest limitations
[1–2 lines. What it flags vs proves; what's curated vs automatic. Shows rigor — judges respect this.]

## Team
[Names + roles]

## License
MIT — [the punk line: "the rebellion is stronger when the next person can continue it."]
```

---

## Submission-day checklist (don't lose points on logistics)
- [ ] Repo is **public**
- [ ] `LICENSE` file = **MIT**, committed
- [ ] README filled + **1 screenshot** of the punch
- [ ] All data-source URLs listed + working
- [ ] `pnpm check` green; repo clones + runs from scratch
- [ ] Demo **backup recording** committed to `/docs`
- [ ] Submitted on the official platform **before the deadline** (don't cut it close)
- [ ] Final commit tagged (e.g. `v1.0-liberhack`)
