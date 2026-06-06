"""Write the ingest contract: normalized NDJSON + raw snapshots + demo samples.

Layout (``.claude/rules/scraping.md`` §2):
- ``normalized/<source>.ndjson`` — one :class:`IngestRecord` per line, UTF-8,
  ``ensure_ascii=False`` (Cyrillic preserved), **deduplicated on natural_key**.
- ``raw/<source>/<hash>.<ext>`` — the original payload, kept so we can re-parse
  without re-fetching and prove provenance.
- ``samples/<source>.ndjson`` — a small real slice committed so a dead upstream
  can't kill the demo.
"""

from __future__ import annotations

import hashlib
from dataclasses import dataclass
from pathlib import Path

from ..config import Config
from ..contract import IngestRecord


@dataclass
class WriteResult:
    source: str
    normalized_path: Path
    written: int
    duplicates: int
    sample_path: Path | None = None
    sample_written: int = 0


class NdjsonSink:
    """Persists records for one source under the shared ingest dir."""

    def __init__(self, config: Config, source: str) -> None:
        self.config = config
        self.source = source

    # -- raw provenance ----------------------------------------------------
    def save_raw(self, content: bytes, *, ext: str = "bin") -> Path:
        """Store a raw payload snapshot keyed by content hash (idempotent)."""
        digest = hashlib.sha256(content).hexdigest()[:40]
        raw_dir = self.config.raw_dir / self.source
        raw_dir.mkdir(parents=True, exist_ok=True)
        path = raw_dir / f"{digest}.{ext.lstrip('.')}"
        if not path.exists():
            path.write_bytes(content)
        return path

    # -- normalized output -------------------------------------------------
    def write(self, records: list[IngestRecord]) -> WriteResult:
        """Write normalized NDJSON, deduplicating on ``natural_key``.

        Last write wins for a duplicate key — re-running a scrape replaces, never
        appends, so output stays idempotent.
        """
        deduped: dict[str, IngestRecord] = {}
        duplicates = 0
        for record in records:
            if record.natural_key in deduped:
                duplicates += 1
            deduped[record.natural_key] = record

        self.config.normalized_dir.mkdir(parents=True, exist_ok=True)
        path = self.config.normalized_dir / f"{self.source}.ndjson"
        lines = [rec.to_ndjson_line() for rec in deduped.values()]
        path.write_text("\n".join(lines) + ("\n" if lines else ""), encoding="utf-8")

        return WriteResult(
            source=self.source,
            normalized_path=path,
            written=len(deduped),
            duplicates=duplicates,
        )

    def write_sample(self, records: list[IngestRecord], limit: int = 25) -> tuple[Path, int]:
        """Write a small committed demo slice to ``samples/<source>.ndjson``."""
        self.config.samples_dir.mkdir(parents=True, exist_ok=True)
        path = self.config.samples_dir / f"{self.source}.ndjson"

        deduped: dict[str, IngestRecord] = {}
        for record in records:
            deduped[record.natural_key] = record
            if len(deduped) >= limit:
                break

        lines = [rec.to_ndjson_line() for rec in deduped.values()]
        path.write_text("\n".join(lines) + ("\n" if lines else ""), encoding="utf-8")
        return path, len(deduped)
