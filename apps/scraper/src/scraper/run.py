"""CLI entrypoint: fetch a source, normalize to the NDJSON ingest contract.

Usage:
  uv run scrape --list                 # show known sources + enabled state
  uv run scrape --source ted           # scrape one source
  uv run scrape --all                  # scrape every ENABLED source
  uv run scrape --source ted --limit 50 --sample 25

Output goes to ``$INGEST_OUT_DIR`` (``storage/ingest`` locally): normalized
NDJSON + raw snapshots + a committed demo sample. Laravel then ingests via
``php artisan ingest:run --source=<x>``. See ``.claude/rules/scraping.md``.
"""

from __future__ import annotations

import argparse
import logging
import sys

from .config import Config, load_config
from .http import PoliteClient
from .registry import all_source_ids, get_source_class
from .sinks import NdjsonSink

log = logging.getLogger("scraper")


def _setup_logging(verbose: bool) -> None:
    # Cyrillic preview/logging must not crash on a legacy (cp1252) Windows console.
    for stream in (sys.stdout, sys.stderr):
        reconfigure = getattr(stream, "reconfigure", None)
        if reconfigure is not None:
            try:
                reconfigure(encoding="utf-8", errors="replace")
            except (ValueError, OSError):
                pass
    logging.basicConfig(
        level=logging.DEBUG if verbose else logging.INFO,
        format="%(levelname)s %(name)s: %(message)s",
    )
    if not verbose:
        # The HTTP libraries are chatty; keep the run summary readable.
        for noisy in ("httpx", "httpcore", "urllib3"):
            logging.getLogger(noisy).setLevel(logging.WARNING)


def run_source(source_id: str, config: Config, *, limit: int | None,
               sample: int) -> int:
    """Scrape one source; returns the number of records written."""
    source_cfg = config.source(source_id)
    source_class = get_source_class(source_id)

    with PoliteClient(config) as client:
        sink = NdjsonSink(config, source_id)
        source = source_class(client, sink, source_cfg, config)
        records = list(source.records(limit=limit))

    result = sink.write(records)
    sample_path, sample_n = sink.write_sample(records, limit=sample) if records else (None, 0)

    skipped = len(source.skipped)
    log.info(
        "[%s] ingested %d, skipped %d, duplicates %d -> %s",
        source_id, result.written, skipped, result.duplicates, result.normalized_path,
    )
    if sample_path:
        log.info("[%s] sample (%d) -> %s", source_id, sample_n, sample_path)
    if source.skipped:
        for key, reason in source.skipped[:10]:
            log.info("[%s]   skipped %s: %s", source_id, key, reason)
    _print_preview(records)
    return result.written


def _print_preview(records: list, n: int = 5) -> None:
    """Print a few records so a human can eyeball the Cyrillic (§3)."""
    for rec in records[:n]:
        print("  " + rec.to_ndjson_line()[:300])


def _cmd_list(config: Config) -> int:
    print("Known sources (enable via <ID>_ENABLED=true):")
    for source_id in all_source_ids():
        cfg = config.source(source_id)
        state = "enabled" if cfg.enabled else "disabled"
        print(f"  {source_id:10s} [{state}]  {cfg.base_url}")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(
        prog="scrape", description="LiberHack procurement/corruption scraper."
    )
    parser.add_argument("--source", help="Source id to scrape (e.g. ted, egov, caiseop).")
    parser.add_argument("--all", action="store_true", help="Scrape every ENABLED source.")
    parser.add_argument("--limit", type=int, default=None, help="Max records to write.")
    parser.add_argument("--sample", type=int, default=25, help="Records in the demo sample.")
    parser.add_argument("--list", action="store_true", help="List known sources and exit.")
    parser.add_argument("--force", action="store_true",
                        help="Run even if the source is disabled in env.")
    parser.add_argument("-v", "--verbose", action="store_true")
    args = parser.parse_args()

    _setup_logging(args.verbose)
    config = load_config()

    if args.list:
        return _cmd_list(config)

    if args.all:
        targets = [s for s in all_source_ids() if config.source(s).enabled or args.force]
    elif args.source:
        targets = [args.source]
    else:
        parser.print_help()
        return 1

    if not targets:
        log.warning("No enabled sources. Enable one (e.g. TED_ENABLED=true) or use --force.")
        return 1

    total = 0
    failures = 0
    for source_id in targets:
        cfg = config.source(source_id)
        if not cfg.enabled and not args.force:
            log.warning("[%s] disabled (set %s_ENABLED=true or --force)",
                        source_id, source_id.upper())
            continue
        try:
            total += run_source(source_id, config, limit=args.limit, sample=args.sample)
        except Exception as exc:  # noqa: BLE001 - one bad source shouldn't kill the run
            failures += 1
            log.error("[%s] failed: %s", source_id, exc)

    log.info("Done. Total records written: %d (%d source failures).", total, failures)
    return 1 if failures and total == 0 else 0


if __name__ == "__main__":
    sys.exit(main())
