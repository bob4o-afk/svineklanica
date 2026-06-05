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
