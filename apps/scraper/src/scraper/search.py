"""CLI: search a source's records — vector (AI) or keyword (script) mode.

Usage:
  uv run search --source ted "монитори за болница"
  uv run search --source caiseop --top 5 "ремонт на пътища"
  uv run search --source ted --mode keyword "компютри"     # force the AI-free path

Two ranking modes, picked by ``SEARCH_MODE`` (or ``--mode`` to override):
  - **vector**  — embeds the query with the same model and ranks the sidecar
    vectors by cosine similarity (needs the ``embed`` extra + `uv run embed`).
  - **keyword** — Okapi BM25 over the normalized corpus, pure Python, no model.

This proves search quality end-to-end; production search runs in the backend
over pgvector (vector mode) — keyword mode is the no-AI fallback.
"""

from __future__ import annotations

import argparse
import logging
import sys
from dataclasses import dataclass

from . import keyword
from .config import SEARCH_MODE_KEYWORD, SEARCH_MODE_VECTOR, load_config, resolve_search_mode
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


def _print_results(source: str, mode: str, query: str, rows: list[tuple[float, str, str]]) -> None:
    print(f'\nТърсене в "{source}" [{mode}]: {query}\n')
    if not rows:
        print("  (няма резултати)")
        return
    for i, (score, text, url) in enumerate(rows, 1):
        print(f"{i:2d}. [{score:.3f}] {text[:140]}")
        print(f"      {url}")


def _run_vector(config, source: str, query: str, top: int, backend, model) -> int:
    records = EmbeddingSink(config, source).read()
    if not records:
        log.warning(
            "No embeddings for '%s'. Run `uv run embed --source %s`, "
            "or use keyword mode: `--mode keyword` / SEARCH_MODE=keyword.",
            source, source,
        )
        return 1
    embedder = get_embedder(backend=backend, model=model)
    hits = rank(embedder.embed_query(query), records, top=top)
    _print_results(source, SEARCH_MODE_VECTOR, query,
                   [(h.score, h.record.text, h.record.source_url) for h in hits])
    return 0


def _run_keyword(config, source: str, query: str, top: int) -> int:
    docs = keyword.load_docs(config, source)
    if not docs:
        log.warning(
            "No normalized records for '%s'. Run `uv run scrape --source %s` first.",
            source, source,
        )
        return 1
    hits = keyword.rank(query, docs, top=top)
    _print_results(source, SEARCH_MODE_KEYWORD, query,
                   [(h.score, h.doc.text, h.doc.source_url) for h in hits])
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(prog="search", description="Search demo (vector or keyword).")
    parser.add_argument("--source", required=True, help="Source id to search.")
    parser.add_argument("query", nargs="+", help="The search query (Bulgarian).")
    parser.add_argument("--top", type=int, default=10, help="Number of results.")
    parser.add_argument(
        "--mode",
        default=None,
        help="Search mode: vector (AI/embeddings) | keyword (script/BM25). "
        "Overrides SEARCH_MODE. Aliases: embedded, normal, text, bm25.",
    )
    parser.add_argument("--backend", default=None, help="Embedding backend (vector mode).")
    parser.add_argument("--model", default=None, help="Embedding model id (vector mode).")
    args = parser.parse_args()

    _setup_logging()
    config = load_config()

    # --mode overrides the env-driven default in config.search_mode.
    if args.mode is None:
        mode = config.search_mode
    else:
        mode = resolve_search_mode(args.mode)
        if mode is None:
            log.error("Unknown --mode '%s' (use: vector | keyword).", args.mode)
            return 2

    query_text = " ".join(args.query)
    if mode == SEARCH_MODE_KEYWORD:
        return _run_keyword(config, args.source, query_text, args.top)
    return _run_vector(config, args.source, query_text, args.top, args.backend, args.model)


if __name__ == "__main__":
    sys.exit(main())
