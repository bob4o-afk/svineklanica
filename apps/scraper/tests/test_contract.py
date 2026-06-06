import json
from datetime import datetime, timezone

import pytest
from pydantic import ValidationError

from scraper.contract import (
    SCHEMA_VERSION,
    Authority,
    CanonicalPayload,
    IngestRecord,
    PaymentDetail,
    RecordType,
    TenderDetail,
    make_record,
)


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


# --- canonical v2 payload (the envelope + typed block seam) ---

def _canonical(**overrides):
    base = dict(
        source="ted",
        natural_key="123456-2024",
        source_url="https://ted.europa.eu/en/notice/-/detail/123456-2024",
        fetched_at=datetime(2026, 6, 5, 12, 0, tzinfo=timezone.utc),
    )
    base.update(overrides)
    return base


def test_canonical_tender_round_trips():
    rec = make_record(
        **_canonical(),
        payload=CanonicalPayload(
            record_type=RecordType.TENDER,
            category="обществена поръчка",
            title="Доставка на лаптопи",
            authority=Authority(name="УМБАЛ Бургас"),
            tender=TenderDetail(cpv_code="30213100", value=200000.0, currency="BGN", status="announced"),
        ),
    )
    payload = json.loads(rec.to_ndjson_line())["payload"]
    assert payload["record_type"] == "tender"
    assert payload["tender"]["cpv_code"] == "30213100"
    assert payload["authority"]["name"] == "УМБАЛ Бургас"
    # None fields are dropped (no null noise); sphere is left for inference.
    assert "sphere" not in payload
    assert "payment" not in payload


def test_canonical_payment_keeps_extra_provenance():
    rec = make_record(
        **_canonical(source="sebra", natural_key="abc"),
        payload=CanonicalPayload(
            record_type=RecordType.PAYMENT,
            category="нерегламентирани плащания",
            title="Плащане",
            payment=PaymentDetail(spender="МВР", amount=12345.67, currency="BGN"),
            raw_row={"col": "стойност"},  # extra='allow' carries raw provenance
        ),
    )
    payload = json.loads(rec.to_ndjson_line())["payload"]
    assert payload["record_type"] == "payment"
    assert payload["payment"]["amount"] == 12345.67
    assert payload["raw_row"] == {"col": "стойност"}


def test_canonical_requires_record_type_and_category():
    with pytest.raises(ValidationError):
        CanonicalPayload(title="x")  # type: ignore[call-arg]  missing record_type + category
