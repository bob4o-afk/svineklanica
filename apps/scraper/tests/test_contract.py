import json
from datetime import datetime, timezone

import pytest
from pydantic import ValidationError

from scraper.contract import SCHEMA_VERSION, IngestRecord


def _record(**overrides) -> IngestRecord:
    base = dict(
        source="ted",
        natural_key="123456-2024",
        source_url="https://ted.europa.eu/en/notice/-/detail/123456-2024",
        fetched_at=datetime(2026, 6, 5, 12, 0, tzinfo=timezone.utc),
        payload={"title": "Доставка на лаптопи"},
    )
    base.update(overrides)
    return IngestRecord(**base)


def test_ndjson_line_preserves_cyrillic():
    line = _record().to_ndjson_line()
    assert "Доставка на лаптопи" in line  # not \u-escaped
    parsed = json.loads(line)
    assert parsed["payload"]["title"] == "Доставка на лаптопи"
    assert parsed["schema_version"] == SCHEMA_VERSION


def test_ndjson_is_single_line():
    assert "\n" not in _record().to_ndjson_line()


def test_extra_fields_forbidden():
    with pytest.raises(ValidationError):
        _record(unexpected="nope")


def test_required_fields():
    with pytest.raises(ValidationError):
        IngestRecord(source="ted")  # type: ignore[call-arg]
