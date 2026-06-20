"""Keyword (non-AI) search over the normalized corpus — Okapi BM25 ranking.

The script-only counterpart to the embedding / vector search in
:mod:`scraper.search`. **No model, no numpy, no optional extras:** it reads the
normalized NDJSON, builds the *same* composed searchable document the embedder
uses (:func:`scraper.searchable.build_document`), tokenizes the Bulgarian text,
and ranks by Okapi BM25.

Selected with ``SEARCH_MODE=keyword`` (see :mod:`scraper.config`); the vector
path is ``SEARCH_MODE=vector``.
"""

from __future__ import annotations

import math
import re
from dataclasses import dataclass, field

from .config import Config
from .corpus import iter_normalized
from .searchable import build_document

# Same tokenizer the hashing embedder uses, so both modes split text identically.
_TOKEN = re.compile(r"\w+", re.UNICODE)

# Okapi BM25 parameters (standard defaults).
_K1 = 1.5
_B = 0.75


def tokenize(text: str) -> list[str]:
    """Lowercase, Unicode-aware word tokens (Cyrillic-safe)."""
    return _TOKEN.findall(text.lower())


@dataclass
class KeywordDoc:
    """One searchable document built from a normalized record."""

    natural_key: str
    source_url: str
    text: str
    tokens: list[str] = field(default_factory=list)


@dataclass
class KeywordHit:
    score: float
    doc: KeywordDoc


def load_docs(config: Config, source: str) -> list[KeywordDoc]:
    """Read ``normalized/<source>.ndjson`` (or the sample) into searchable docs."""
    docs: list[KeywordDoc] = []
    for record in iter_normalized(config, source):
        payload = record.get("payload") or {}
        text = build_document(source, payload)
        docs.append(
            KeywordDoc(
                natural_key=record.get("natural_key", ""),
                source_url=record.get("source_url", ""),
                text=text,
                tokens=tokenize(text),
            )
        )
    return docs


def rank(query: str, docs: list[KeywordDoc], top: int = 10) -> list[KeywordHit]:
    """Return the ``top`` docs most relevant to ``query`` by Okapi BM25."""
    query_terms = tokenize(query)
    n = len(docs)
    if n == 0 or not query_terms:
        return []

    avgdl = sum(len(d.tokens) for d in docs) / n or 1.0

    # term frequency per doc + document frequency across the corpus
    doc_tfs: list[dict[str, int]] = []
    df: dict[str, int] = {}
    for d in docs:
        tf: dict[str, int] = {}
        for tok in d.tokens:
            tf[tok] = tf.get(tok, 0) + 1
        doc_tfs.append(tf)
        for term in tf:
            df[term] = df.get(term, 0) + 1

    hits: list[KeywordHit] = []
    for d, tf in zip(docs, doc_tfs):
        dl = len(d.tokens) or 1
        score = 0.0
        for term in query_terms:
            f = tf.get(term, 0)
            if f == 0:
                continue
            idf = math.log(1 + (n - df[term] + 0.5) / (df[term] + 0.5))
            denom = f + _K1 * (1 - _B + _B * dl / avgdl)
            score += idf * (f * (_K1 + 1)) / denom
        if score > 0:
            hits.append(KeywordHit(score=score, doc=d))

    hits.sort(key=lambda h: h.score, reverse=True)
    return hits[:top]
