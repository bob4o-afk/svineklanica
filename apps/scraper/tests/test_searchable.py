"""build_document composes the right searchable text per source."""

from __future__ import annotations

from scraper.searchable import build_document
from scraper.sources.caiseop import CaiseopSource
from scraper.sources.ted import TedSource


def test_ted_document(make_source, payload_factory):
    src = make_source(TedSource, base_url="https://ted.europa.eu")
    payload = payload_factory("ted_sample.json", "https://ted.europa.eu/en/", ext="json")
    rec = next(iter(src.parse(payload)))
    doc = build_document("ted", rec.payload)
    assert "медицинско оборудване" in doc
    assert "УМБАЛ Бургас" in doc
    assert "CPV 33100000" in doc
    assert "http" not in doc  # no urls leaking in


def test_caiseop_document_has_subject_and_entities(make_source, payload_factory):
    src = make_source(CaiseopSource)
    payload = payload_factory("caiseop_sample.csv", "https://data.egov.bg/x.csv", ext="csv")
    rec = next(iter(src.parse(payload)))
    doc = build_document("caiseop", rec.payload)
    assert "преносими компютри" in doc
    assert "Община Бургас" in doc
    assert "Техно Трейд" in doc


def test_generic_fallback_collects_strings_skips_urls():
    payload = {"row": {"a": "Доставка на горива", "b": "https://example.bg/x", "c": "Община Русе"}}
    doc = build_document("egov", payload)
    assert "Доставка на горива" in doc
    assert "Община Русе" in doc


def test_empty_payload_returns_empty():
    assert build_document("ted", {}) == ""
