"""Python scraping layer for the LiberHack "corruption fucker" watchdog.

Fetches public Bulgarian data, normalizes it (Cyrillic-safe), and writes the
NDJSON ingest contract that the Laravel backend reads. Laravel never scrapes.
See /.claude/rules/scraping.md and ./contract.py for the seam.
"""
