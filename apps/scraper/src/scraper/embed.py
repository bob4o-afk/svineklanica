"""CLI: embed a source's normalized records into the sidecar vectors.

Usage:
  uv run embed --source ted
  uv run embed --source ted --limit 500 --batch 64 --sample 10

Reads ``normalized/<source>.ndjson`` (or the committed sample), composes the
searchable document per record, embeds in batches, and writes
``embeddings/<source>.ndjson`` (+ a small committed sample). The backend loads
these into pgvector and joins on ``natural_key``. See SOURCES.md.
"""

from __future__ import annotations

import argparse
import logging
import sys

from .config import Config, load_config
from .corpus import iter_normalized
from .embeddings import Embedder, get_embedder
from .searchable import build_document
from .sinks.embeddings import EmbedRecord, EmbeddingSink

log = logging.getLogger("scraper.embed")


def _setup_logging(verbose: bool) -> None:
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


def _batched(items: list, size: int):
    for i in range(0, len(items), size):
        yield items[i : i + size]


def embed_source(source: str, config: Config, embedder: Embedder, *,
                 limit: int | None, batch: int, sample: int) -> int:
    """Embed one source's corpus into the sidecar; returns records written."""
    docs: list[tuple[str, str, str]] = []  # (natural_key, source_url, text)
    for rec in iter_normalized(config, source):
        text = build_document(source, rec.get("payload", {}))
        if not text:
            continue
        docs.append((rec["natural_key"], rec.get("source_url", ""), text))
        if limit is not None and len(docs) >= limit:
            break

    out: list[EmbedRecord] = []
    for chunk in _batched(docs, max(1, batch)):
        vectors = embedder.embed_documents([d[2] for d in chunk])
        for (natural_key, source_url, text), vec in zip(chunk, vectors):
            out.append(EmbedRecord(
                source=source, natural_key=natural_key, source_url=source_url,
                model=embedder.model, dim=len(vec), text=text, embedding=vec,
            ))

    sink = EmbeddingSink(config, source)
    path = sink.write(out)
    sample_path, sample_n = sink.write_sample(out, limit=sample) if out else (None, 0)

    log.info("[%s] embedded %d records (model=%s, dim=%s) -> %s",
             source, len(out), embedder.model, embedder.dim, path)
    if sample_path:
        log.info("[%s] sample (%d) -> %s", source, sample_n, sample_path)
    return len(out)


def main() -> int:
    parser = argparse.ArgumentParser(prog="embed", description="Embed scraped records.")
    parser.add_argument("--source", required=True, help="Source id (e.g. ted, caiseop).")
    parser.add_argument("--limit", type=int, default=None, help="Max records to embed.")
    parser.add_argument("--batch", type=int, default=64, help="Embedding batch size.")
    parser.add_argument("--sample", type=int, default=10, help="Records in the committed sample.")
    parser.add_argument("--backend", default=None, help="fastembed|sentence-transformers|hashing")
    parser.add_argument("--model", default=None, help="Embedding model id.")
    parser.add_argument("-v", "--verbose", action="store_true")
    args = parser.parse_args()

    _setup_logging(args.verbose)
    config = load_config()
    embedder = get_embedder(backend=args.backend, model=args.model)
    written = embed_source(args.source, config, embedder,
                           limit=args.limit, batch=args.batch, sample=args.sample)
    if written == 0:
        log.warning("[%s] nothing embedded — is normalized/%s.ndjson present?",
                    args.source, args.source)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
