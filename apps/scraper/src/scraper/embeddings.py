"""Embedding backends for semantic search over scraped procurement records.

Embeddings are produced **in Python** (``.claude/rules/scraping.md`` §8,
``backend.md`` §12); the Laravel backend only stores + indexes them in pgvector.

Default backend is **fastembed** (ONNX, CPU, no torch) with a Bulgarian-aware
multilingual model. A dependency-free :class:`HashingEmbedder` is used in tests
(and as an offline fallback) so CI never downloads a model.

Model choice is configurable via ``EMBED_MODEL`` / ``EMBED_BACKEND``. We default
to ``sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`` — a small
(384-dim) multilingual model that handles Bulgarian well and runs fast on CPU
via fastembed's ONNX runtime. If an E5 model is selected instead, the required
``query:`` / ``passage:`` prefixes are applied automatically.
"""

from __future__ import annotations

import hashlib
import math
import os
import re
from typing import Protocol, runtime_checkable

DEFAULT_MODEL = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
DEFAULT_BACKEND = "fastembed"

_TOKEN = re.compile(r"\w+", re.UNICODE)


@runtime_checkable
class Embedder(Protocol):
    """Produces L2-normalized vectors for documents and queries."""

    model: str
    dim: int

    def embed_documents(self, texts: list[str]) -> list[list[float]]: ...

    def embed_query(self, text: str) -> list[float]: ...


def _is_e5(model: str) -> bool:
    return "e5" in model.lower()


class FastEmbedEmbedder:
    """fastembed (ONNX) backend — lightweight, CPU-friendly, no torch."""

    def __init__(self, model: str = DEFAULT_MODEL) -> None:
        try:
            from fastembed import TextEmbedding
        except ImportError as exc:  # pragma: no cover - depends on optional extra
            raise RuntimeError(
                "The embedding extra is not installed. Install it with:\n"
                "  uv sync --extra embed"
            ) from exc

        self.model = model
        self._e5 = _is_e5(model)
        self._engine = TextEmbedding(model_name=model)
        self.dim = self._probe_dim()

    def _probe_dim(self) -> int:
        vec = next(iter(self._engine.embed(["passage: x"])))
        return int(len(vec))

    def _prefix(self, text: str, kind: str) -> str:
        return f"{kind}: {text}" if self._e5 else text

    def embed_documents(self, texts: list[str]) -> list[list[float]]:
        prepped = [self._prefix(t, "passage") for t in texts]
        vectors = [[float(x) for x in v] for v in self._engine.embed(prepped)]
        if vectors:
            self.dim = len(vectors[0])
        return vectors

    def embed_query(self, text: str) -> list[float]:
        prepped = self._prefix(text, "query")
        return [float(x) for x in next(iter(self._engine.embed([prepped])))]


class SentenceTransformersEmbedder:
    """sentence-transformers backend (heavier; needs the ``embed-st`` extra)."""

    def __init__(self, model: str = DEFAULT_MODEL) -> None:
        try:
            from sentence_transformers import SentenceTransformer
        except ImportError as exc:  # pragma: no cover - optional extra
            raise RuntimeError(
                "sentence-transformers is not installed. Install it with:\n"
                "  uv sync --extra embed-st"
            ) from exc

        self.model = model
        self._e5 = _is_e5(model)
        self._engine = SentenceTransformer(model)
        self.dim = int(self._engine.get_sentence_embedding_dimension())

    def _prefix(self, text: str, kind: str) -> str:
        return f"{kind}: {text}" if self._e5 else text

    def embed_documents(self, texts: list[str]) -> list[list[float]]:
        prepped = [self._prefix(t, "passage") for t in texts]
        vecs = self._engine.encode(prepped, normalize_embeddings=True)
        return [[float(x) for x in v] for v in vecs]

    def embed_query(self, text: str) -> list[float]:
        vec = self._engine.encode(self._prefix(text, "query"), normalize_embeddings=True)
        return [float(x) for x in vec]


class HashingEmbedder:
    """Deterministic, dependency-free embedder (tests / offline fallback).

    Hashes tokens into a fixed-dim space with signed buckets, then L2-normalizes.
    Texts that share tokens get similar vectors, so cosine ranking is meaningful
    without downloading any model.
    """

    def __init__(self, dim: int = 64) -> None:
        self.model = "hashing-test"
        self.dim = dim

    def _vector(self, text: str) -> list[float]:
        vec = [0.0] * self.dim
        for token in _TOKEN.findall(text.lower()):
            digest = int(hashlib.md5(token.encode("utf-8")).hexdigest(), 16)
            idx = digest % self.dim
            sign = 1.0 if (digest >> 8) & 1 else -1.0
            vec[idx] += sign
        norm = math.sqrt(sum(x * x for x in vec)) or 1.0
        return [x / norm for x in vec]

    def embed_documents(self, texts: list[str]) -> list[list[float]]:
        return [self._vector(t) for t in texts]

    def embed_query(self, text: str) -> list[float]:
        return self._vector(text)


def get_embedder(backend: str | None = None, model: str | None = None) -> Embedder:
    """Build an :class:`Embedder` from explicit args or the environment."""
    backend = (backend or os.environ.get("EMBED_BACKEND", DEFAULT_BACKEND)).lower()
    model = model or os.environ.get("EMBED_MODEL", DEFAULT_MODEL)

    if backend == "hashing":
        return HashingEmbedder()
    if backend == "fastembed":
        return FastEmbedEmbedder(model)
    if backend in {"sentence-transformers", "sentence_transformers", "st"}:
        return SentenceTransformersEmbedder(model)
    raise ValueError(f"Unknown EMBED_BACKEND '{backend}' (fastembed|sentence-transformers|hashing)")
