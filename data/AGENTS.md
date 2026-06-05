# AGENTS.md — /data layer

You are working in the **data layer**. Read this folder's [`README.md`](./README.md) first, then the root [`/CONTEXT.md`](../CONTEXT.md).

Scrape real public BG data → normalize (Cyrillic = UTF-8 via chardet/cp1251) → write a clean `.duckdb`. Document every table in `SCHEMA.md`. Own provenance (source URL + date). Stay in `/data`.
