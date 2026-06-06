"""Pytest config: offline by default (stub LLM, zero tokens).

The ``llm`` marker hits the real Gemini API and is skipped unless ``--run-llm``
is passed *and* ``GOOGLE_API_KEY`` is set.
"""

from __future__ import annotations

import os

import pytest
from pydantic import BaseModel


def pytest_addoption(parser):
    parser.addoption("--run-llm", action="store_true", default=False, help="run live Gemini tests")


def pytest_collection_modifyitems(config, items):
    run = config.getoption("--run-llm") and os.environ.get("GOOGLE_API_KEY")
    if run:
        return
    skip = pytest.mark.skip(reason="needs --run-llm and GOOGLE_API_KEY")
    for item in items:
        if "llm" in item.keywords:
            item.add_marker(skip)


class StubClient:
    """A StructuredLLM stub: returns canned outputs by schema (else schema())."""

    available = True

    def __init__(self, mapping: dict | None = None) -> None:
        self.mapping = mapping or {}
        self.calls: list[str] = []

    def analyze(self, system_md: str, user_text: str, schema: type[BaseModel]):
        self.calls.append(schema.__name__)
        if schema in self.mapping:
            return self.mapping[schema]
        return schema()


@pytest.fixture
def stub_client():
    return StubClient


def make_record(**payload) -> dict:
    """Build a minimal IngestRecord dict with a real-looking source_url."""
    natural_key = payload.pop("natural_key", "TEST-1")
    source = payload.pop("source", "ted")
    source_url = payload.pop("source_url", f"https://ted.europa.eu/notice/{natural_key}")
    return {
        "source": source,
        "natural_key": natural_key,
        "source_url": source_url,
        "fetched_at": "2026-06-05T00:00:00Z",
        "schema_version": 1,
        "payload": payload,
    }


@pytest.fixture
def record_factory():
    return make_record
