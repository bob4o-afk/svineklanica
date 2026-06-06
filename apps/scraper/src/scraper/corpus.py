"""Read normalized NDJSON back in (the corpus we embed / search over)."""

from __future__ import annotations

import json
from collections.abc import Iterator
from pathlib import Path

from .config import Config


def normalized_path(config: Config, source: str) -> Path:
    path = config.normalized_dir / f"{source}.ndjson"
    if path.exists():
        return path
    # Fall back to the committed demo sample so a clean checkout can embed/search.
    return config.samples_dir / f"{source}.ndjson"


def iter_normalized(config: Config, source: str) -> Iterator[dict]:
    """Yield parsed records from ``normalized/<source>.ndjson`` (or the sample)."""
    path = normalized_path(config, source)
    if not path.exists():
        return
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if line:
            yield json.loads(line)
