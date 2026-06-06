"""Sidecar embeddings output (keeps the IngestRecord contract untouched).

Writes ``embeddings/<source>.ndjson`` — one line per record, keyed by
``natural_key`` and tagged with the model + dim, so the backend can load the
vectors into pgvector and join back on ``natural_key`` without any change to the
normalized ingest contract.

Line shape:
    {"source","natural_key","source_url","model","dim","text","embedding":[...]}
"""

from __future__ import annotations

import json
from dataclasses import dataclass
from pathlib import Path

from ..config import Config


@dataclass
class EmbedRecord:
    source: str
    natural_key: str
    source_url: str
    model: str
    dim: int
    text: str
    embedding: list[float]

    def to_line(self) -> str:
        return json.dumps(
            {
                "source": self.source,
                "natural_key": self.natural_key,
                "source_url": self.source_url,
                "model": self.model,
                "dim": self.dim,
                "text": self.text,
                "embedding": self.embedding,
            },
            ensure_ascii=False,
        )

    @classmethod
    def from_dict(cls, data: dict) -> EmbedRecord:
        return cls(
            source=data["source"],
            natural_key=data["natural_key"],
            source_url=data.get("source_url", ""),
            model=data.get("model", ""),
            dim=int(data.get("dim", len(data.get("embedding", [])))),
            text=data.get("text", ""),
            embedding=[float(x) for x in data.get("embedding", [])],
        )


class EmbeddingSink:
    """Persists embedding sidecar files under the shared ingest dir."""

    def __init__(self, config: Config, source: str) -> None:
        self.config = config
        self.source = source

    def path(self) -> Path:
        return self.config.embeddings_dir / f"{self.source}.ndjson"

    def sample_path(self) -> Path:
        return self.config.samples_dir / "embeddings" / f"{self.source}.ndjson"

    def write(self, records: list[EmbedRecord]) -> Path:
        """Write embeddings, deduplicated on ``natural_key`` (idempotent)."""
        deduped: dict[str, EmbedRecord] = {r.natural_key: r for r in records}
        self.config.embeddings_dir.mkdir(parents=True, exist_ok=True)
        path = self.path()
        lines = [r.to_line() for r in deduped.values()]
        path.write_text("\n".join(lines) + ("\n" if lines else ""), encoding="utf-8")
        return path

    def write_sample(self, records: list[EmbedRecord], limit: int = 10) -> tuple[Path, int]:
        path = self.sample_path()
        path.parent.mkdir(parents=True, exist_ok=True)
        deduped: dict[str, EmbedRecord] = {}
        for r in records:
            deduped[r.natural_key] = r
            if len(deduped) >= limit:
                break
        lines = [r.to_line() for r in deduped.values()]
        path.write_text("\n".join(lines) + ("\n" if lines else ""), encoding="utf-8")
        return path, len(deduped)

    def read(self) -> list[EmbedRecord]:
        """Load the sidecar (preferring the full file, falling back to sample)."""
        path = self.path()
        if not path.exists():
            path = self.sample_path()
        if not path.exists():
            return []
        records: list[EmbedRecord] = []
        for line in path.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if line:
                records.append(EmbedRecord.from_dict(json.loads(line)))
        return records
