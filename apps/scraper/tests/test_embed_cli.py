"""embed_source reads normalized NDJSON and writes the sidecar (with hashing)."""

from __future__ import annotations

import json

from scraper.embed import embed_source
from scraper.embeddings import HashingEmbedder
from scraper.sinks.embeddings import EmbeddingSink

CORPUS = [
    {"source": "ted", "natural_key": "1", "source_url": "https://ted/1",
     "fetched_at": "2026-06-05T12:00:00Z", "schema_version": 2,
     "payload": {"record_type": "tender", "category": "обществена поръчка",
                 "title": "Доставка на лаптопи", "authority": {"name": "Община Бургас"},
                 "tender": {"cpv_code": "30213100"}}},
    {"source": "ted", "natural_key": "2", "source_url": "https://ted/2",
     "fetched_at": "2026-06-05T12:00:00Z", "schema_version": 2,
     "payload": {"record_type": "tender", "category": "обществена поръчка",
                 "title": "Ремонт на пътища", "authority": {"name": "Община Варна"},
                 "tender": {"cpv_code": "45233142"}}},
]


def _write_normalized(config, source, rows):
    config.normalized_dir.mkdir(parents=True, exist_ok=True)
    path = config.normalized_dir / f"{source}.ndjson"
    path.write_text("\n".join(json.dumps(r, ensure_ascii=False) for r in rows) + "\n",
                    encoding="utf-8")


def test_embed_source_writes_sidecar(make_config):
    config = make_config()
    _write_normalized(config, "ted", CORPUS)

    written = embed_source("ted", config, HashingEmbedder(), limit=None, batch=2, sample=5)
    assert written == 2

    records = EmbeddingSink(config, "ted").read()
    assert len(records) == 2
    first = next(r for r in records if r.natural_key == "1")
    assert first.model == "hashing-test"
    assert first.dim == len(first.embedding) == 64
    assert "Доставка на лаптопи" in first.text
    assert "Община Бургас" in first.text


def test_embed_source_idempotent(make_config):
    config = make_config()
    _write_normalized(config, "ted", CORPUS)
    embed_source("ted", config, HashingEmbedder(), limit=None, batch=64, sample=5)
    embed_source("ted", config, HashingEmbedder(), limit=None, batch=64, sample=5)
    lines = EmbeddingSink(config, "ted").path().read_text(encoding="utf-8").splitlines()
    assert len(lines) == 2  # no duplication on re-run


def test_embed_source_empty_when_no_corpus(make_config):
    config = make_config()
    assert embed_source("ted", config, HashingEmbedder(), limit=None, batch=64, sample=5) == 0
