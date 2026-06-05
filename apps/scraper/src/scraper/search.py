"""CLI: semantic search over a source's sidecar embeddings (demo / sanity).

Usage:
  uv run search --source ted "монитори за болница"
  uv run search --source caiseop --top 5 "ремонт на пътища"

Embeds the query with the same model, ranks the sidecar vectors by cosine
similarity, and prints the top matches with their source URL. This proves the
search quality end-to-end in pure Python; the production search runs in the
backend over pgvector.
"""

from __future__ import annotations

import argparse
import logging
import sys
from dataclasses import dataclass

from .config import load_config
from .embeddings import get_embedder
from .sinks.embeddings import EmbedRecord, EmbeddingSink

log = logging.getLogger("scraper.search")


@dataclass
class SearchHit:
    score: float
    record: EmbedRecord


def _cosine(query: list[float], matrix: list[list[float]]) -> list[float]:
    import numpy as np

    if not matrix:
        return []
    q = np.asarray(query, dtype=float)
    m = np.asarray(matrix, dtype=float)
    qn = np.linalg.norm(q) or 1.0
    mn = np.linalg.norm(m, axis=1)
    mn[mn == 0] = 1.0
    return (m @ q / (mn * qn)).tolist()


def rank(query_vec: list[float], records: list[EmbedRecord], top: int = 10) -> list[SearchHit]:
    """Return the ``top`` records most cosine-similar to ``query_vec``."""
    scores = _cosine(query_vec, [r.embedding for r in records])
    hits = [SearchHit(score=s, record=r) for s, r in zip(scores, records)]
    hits.sort(key=lambda h: h.score, reverse=True)
    return hits[:top]


def _setup_logging() -> None:
    for stream in (sys.stdout, sys.stderr):
        reconfigure = getattr(stream, "reconfigure", None)
        if reconfigure is not None:
            try:
                reconfigure(encoding="utf-8", errors="replace")
            except (ValueError, OSError):
                pass
    logging.basicConfig(level=logging.INFO, format="%(levelname)s %(name)s: %(message)s")
    for noisy in ("httpx", "httpcore"):
        logging.getLogger(noisy).setLevel(logging.WARNING)


def main() -> int:
    parser = argparse.ArgumentParser(prog="search", description="Semantic search demo.")
    parser.add_argument("--source", required=True, help="Source id to search.")
    parser.add_argument("query", nargs="+", help="The search query (Bulgarian).")
    parser.add_argument("--top", type=int, default=10, help="Number of results.")
    parser.add_argument("--backend", default=None, help="Embedding backend.")
    parser.add_argument("--model", default=None, help="Embedding model id.")
    args = parser.parse_args()

    _setup_logging()
    config = load_config()
    records = EmbeddingSink(config, args.source).read()
    if not records:
        log.warning("No embeddings for '%s'. Run: uv run embed --source %s",
                    args.source, args.source)
        return 1

    embedder = get_embedder(backend=args.backend, model=args.model)
    query_text = " ".join(args.query)
    hits = rank(embedder.embed_query(query_text), records, top=args.top)

    print(f'\nТърсене в "{args.source}": {query_text}\n')
    for i, hit in enumerate(hits, 1):
        print(f"{i:2d}. [{hit.score:.3f}] {hit.record.text[:140]}")
        print(f"      {hit.record.source_url}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
