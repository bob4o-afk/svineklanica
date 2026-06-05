"""Opt-in live smoke tests. Run with: ``uv run pytest --run-network``.

These hit real upstreams and are skipped by default so CI stays deterministic
and we never hammer a source. They prove the fetch path end-to-end.
"""

from __future__ import annotations

import pytest

from scraper.config import load_config
from scraper.http import PoliteClient
from scraper.sinks import NdjsonSink
from scraper.sources.ted import TedSource


@pytest.mark.network
def test_ted_live_returns_bulgarian_notices(tmp_path):
    config = load_config()
    object.__setattr__(config, "ingest_out_dir", tmp_path)
    with PoliteClient(config) as client:
        source = TedSource(client, NdjsonSink(config, "ted"), config.source("ted"), config)
        records = list(source.records(limit=5))

    assert records, "TED returned no notices"
    assert all(r.source == "ted" for r in records)
    assert all(r.source_url.startswith("https://ted.europa.eu/") for r in records)
    assert all(r.natural_key for r in records)


@pytest.mark.network
def test_real_model_ranks_bulgarian_query():
    """Downloads the real multilingual model and checks Bulgarian retrieval."""
    from scraper.embeddings import get_embedder
    from scraper.search import rank
    from scraper.sinks.embeddings import EmbedRecord

    embedder = get_embedder(backend="fastembed")
    corpus = {
        "med": "Доставка на медицинско оборудване за болница",
        "road": "Ремонт на улична настилка и пътища",
        "food": "Доставка на хранителни продукти за училища",
    }
    records = [
        EmbedRecord(source="ted", natural_key=k, source_url=f"https://x/{k}",
                    model=embedder.model, dim=embedder.dim, text=t,
                    embedding=embedder.embed_documents([t])[0])
        for k, t in corpus.items()
    ]
    hits = rank(embedder.embed_query("апаратура за болница"), records, top=3)
    assert hits[0].record.natural_key == "med"
