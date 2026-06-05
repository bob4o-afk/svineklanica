# /data — Data layer (scraping → DuckDB)

**Owner:** Defender · **Stack:** Python · httpx + BeautifulSoup (→ Playwright only if JS-rendered) → DuckDB.

> Read [`/CONTEXT.md`](../CONTEXT.md) and [`/docs/master_rules.md`](../docs/master_rules.md) before writing.

## Your remit
- Scrape/ingest **real public BG data**, normalize it, write a clean `.duckdb` file for `/api` to read.
- Own **data provenance**: every dataset has a source URL + date. You answer the judges' "where's this from?" in Q&A.

## The one hard rule (Seam 1)
- The **DuckDB table schema** is a contract. Document every table + column in **`SCHEMA.md`** (create it when you make the first table). Don't rename a column without updating `SCHEMA.md` and telling the API owner.

## Cyrillic / encoding (this is where projects break)
- Read **bytes** → `chardet` detect → decode (`cp1251` for legacy gov sites, else `utf-8`) → store **UTF-8**.
- `json.dump(..., ensure_ascii=False)`. Spot-check `ще / ъ / я` render correctly before declaring a dataset clean.

## Layout (suggested)
```
data/
  raw/        # downloaded source files (gitignored — heavy)
  curated/    # small committed datasets (tracked)
  ingest/     # scraping + normalize scripts
  SCHEMA.md   # the data→api contract (create with first table)
  *.duckdb    # output (gitignored; rebuilt by scripts)
```

## Scaffold (when the idea is chosen)
```bash
uv init . && uv add httpx beautifulsoup4 chardet duckdb pandas
```
*(Empty shell for now — environment-only.)*
