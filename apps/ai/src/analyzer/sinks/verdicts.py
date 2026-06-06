"""Write verdicts to the sidecar NDJSON the Laravel backend ingests.

Mirrors the scraper's NDJSON sinks: one JSON object per line, UTF-8, Cyrillic
preserved, idempotent on ``natural_key`` (re-running replaces, never dupes). A
small committed sample slice lets the demo run on a clean checkout.
"""

from __future__ import annotations

import json
from dataclasses import dataclass
from pathlib import Path

from ..schemas import VerdictRecord


@dataclass
class WriteResult:
    source: str
    written: int
    path: Path
    sample_path: Path | None = None


class VerdictSink:
    def __init__(self, verdicts_dir: Path, samples_dir: Path) -> None:
        self.verdicts_dir = verdicts_dir
        self.samples_dir = samples_dir

    def _path(self, source: str) -> Path:
        return self.verdicts_dir / f"{source}.ndjson"

    def write(self, source: str, records: list[VerdictRecord], *, sample_size: int = 5) -> WriteResult:
        self.verdicts_dir.mkdir(parents=True, exist_ok=True)

        merged = self._merge_existing(source, records)
        path = self._path(source)
        lines = [r.to_ndjson_line() for r in merged]
        path.write_text("\n".join(lines) + ("\n" if lines else ""), encoding="utf-8")

        sample_path = None
        if records:
            sample_path = self._write_sample(source, records, sample_size)

        return WriteResult(source=source, written=len(records), path=path, sample_path=sample_path)

    def _merge_existing(self, source: str, records: list[VerdictRecord]) -> list[VerdictRecord]:
        """Upsert on natural_key: new records replace old ones with the same key."""
        by_key: dict[str, VerdictRecord] = {}
        path = self._path(source)
        if path.exists():
            for line in path.read_text(encoding="utf-8").splitlines():
                line = line.strip()
                if not line:
                    continue
                try:
                    existing = VerdictRecord.model_validate_json(line)
                    by_key[existing.natural_key] = existing
                except Exception:  # noqa: BLE001 - skip a corrupt line, don't crash
                    continue
        for r in records:
            by_key[r.natural_key] = r
        return list(by_key.values())

    def _write_sample(self, source: str, records: list[VerdictRecord], sample_size: int) -> Path:
        self.samples_dir.mkdir(parents=True, exist_ok=True)
        sample_path = self.samples_dir / f"{source}.ndjson"
        sample = records[:sample_size]
        sample_path.write_text(
            "\n".join(r.to_ndjson_line() for r in sample) + "\n", encoding="utf-8"
        )
        return sample_path

    def read(self, source: str) -> list[VerdictRecord]:
        path = self._path(source)
        if not path.exists():
            path = self.samples_dir / f"{source}.ndjson"
        if not path.exists():
            return []
        out: list[VerdictRecord] = []
        for line in path.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if line:
                out.append(VerdictRecord.model_validate_json(line))
        return out


def to_jsonl(records: list[VerdictRecord]) -> str:
    return "\n".join(r.to_ndjson_line() for r in records)


def dumps(record: VerdictRecord) -> str:
    return json.dumps(record.model_dump(mode="json"), ensure_ascii=False)
