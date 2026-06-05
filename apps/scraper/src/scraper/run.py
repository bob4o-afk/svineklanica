"""CLI entrypoint for the scraper.

STUB until the hackathon. The real wiring is written on site:
  - sources/<x>.py   : fetch + parse one source (httpx + BeautifulSoup/lxml; Playwright only if JS-rendered)
  - normalize.py     : map raw -> IngestRecord (Cyrillic via chardet -> cp1251/utf-8)
  - sinks/ndjson.py  : write records to ./storage/ingest/normalized/<source>.ndjson + raw snapshot

Contract + discipline: ./contract.py and /.claude/rules/scraping.md.
"""

from __future__ import annotations

import argparse
import sys


def main() -> int:
    parser = argparse.ArgumentParser(prog="scrape", description="LiberHack procurement/corruption scraper.")
    parser.add_argument("--source", help="Source id to scrape (e.g. ted, egov, aop).")
    args = parser.parse_args()

    print(
        "procurement-scraper is scaffolded but has no source modules yet.\n"
        f"Requested source: {args.source or '(none)'}\n"
        "Add sources/<x>.py + normalize.py + sinks/ndjson.py on site, then:\n"
        "  uv run scrape --source <x>\n"
        "Output contract: ./storage/ingest/normalized/<source>.ndjson  (see apps/scraper/README.md)"
    )
    return 0


if __name__ == "__main__":
    sys.exit(main())
