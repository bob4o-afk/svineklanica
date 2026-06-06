"""Embedder behavior — exercised with the deterministic HashingEmbedder."""

from __future__ import annotations

import math

import pytest

from scraper.embeddings import HashingEmbedder, get_embedder


def _cos(a, b):
    dot = sum(x * y for x, y in zip(a, b))
    na = math.sqrt(sum(x * x for x in a)) or 1.0
    nb = math.sqrt(sum(x * x for x in b)) or 1.0
    return dot / (na * nb)


def test_hashing_is_deterministic():
    e = HashingEmbedder()
    assert e.embed_query("доставка на лаптопи") == e.embed_query("доставка на лаптопи")


def test_hashing_vectors_are_normalized():
    e = HashingEmbedder(dim=64)
    vec = e.embed_query("ремонт на улици в София")
    assert len(vec) == 64
    assert abs(math.sqrt(sum(x * x for x in vec)) - 1.0) < 1e-9


def test_shared_tokens_increase_similarity():
    e = HashingEmbedder(dim=256)
    base = e.embed_query("доставка на медицинско оборудване за болница")
    near = e.embed_query("доставка на оборудване за болница")
    far = e.embed_query("ремонт на пътна настилка")
    assert _cos(base, near) > _cos(base, far)


def test_embed_documents_matches_count():
    e = HashingEmbedder()
    vecs = e.embed_documents(["а", "б", "в"])
    assert len(vecs) == 3
    assert all(len(v) == e.dim for v in vecs)


def test_get_embedder_hashing():
    assert isinstance(get_embedder(backend="hashing"), HashingEmbedder)


def test_get_embedder_unknown_raises():
    with pytest.raises(ValueError):
        get_embedder(backend="does-not-exist")
