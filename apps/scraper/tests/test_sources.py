"""Offline parser tests — each source's ``parse`` against a committed fixture."""

from __future__ import annotations

from scraper.sources.aop import AopSource
from scraper.sources.caiseop import CaiseopSource
from scraper.sources.egov import EgovSource
from scraper.sources.eop import EopSource
from scraper.sources.isun import IsunSource
from scraper.sources.sebra import SebraSource
from scraper.sources.ted import TedSource


def test_caiseop_parse(make_source, payload_factory):
    src = make_source(CaiseopSource)
    payload = payload_factory("caiseop_sample.csv", "https://data.egov.bg/x.csv", ext="csv")
    records = list(src.parse(payload))

    assert len(records) == 3
    first = records[0]
    assert first.source == "caiseop"
    assert first.natural_key == "00123-2024-0001"
    assert first.payload["subject"] == "Доставка на преносими компютри"
    assert first.payload["value"]["amount"] == 120000.50
    assert first.payload["value"]["currency"] == "BGN"
    assert first.payload["cpv"] == "30213100"
    assert first.payload["winner"]["eik"] == "100000026"
    assert first.payload["winner"]["eik_valid"] is True
    assert first.payload["signed_at"].startswith("2024-03-15")

    # row 2 has a bogus winner EIK -> flagged invalid, not dropped
    assert records[1].payload["winner"]["eik"] == "999999999"
    assert records[1].payload["winner"]["eik_valid"] is False


def test_caiseop_idempotent_keys(make_source, payload_factory):
    src = make_source(CaiseopSource)
    payload = payload_factory("caiseop_sample.csv", "https://data.egov.bg/x.csv", ext="csv")
    keys = [r.natural_key for r in src.parse(payload)]
    assert keys == ["00123-2024-0001", "00123-2024-0002", "00123-2024-0003"]


def test_egov_parse(make_source, payload_factory):
    src = make_source(EgovSource, base_url="https://data.egov.bg")
    payload = payload_factory("egov_sample.json", "https://data.egov.bg/data/view/r1",
                              ext="json", meta={"resource_uri": "r1"})
    records = list(src.parse(payload))
    assert len(records) == 2
    assert records[0].natural_key == "r1:1"
    assert records[0].payload["row"]["възложител"] == "Община Пловдив"


def test_ted_parse_language_maps(make_source, payload_factory):
    src = make_source(TedSource, base_url="https://ted.europa.eu")
    payload = payload_factory("ted_sample.json", "https://ted.europa.eu/en/", ext="json")
    records = list(src.parse(payload))

    assert len(records) == 2
    r = records[0]
    assert r.natural_key == "123456-2024"
    assert r.payload["title"] == "Доставка на медицинско оборудване"
    assert r.payload["buyer"] == "УМБАЛ Бургас"
    assert r.payload["buyer_country"] == "BGR"
    assert r.payload["value"]["amount"] == 2500000.0
    assert r.payload["cpv"] == "33100000"
    assert r.source_url == "https://ted.europa.eu/en/notice/-/detail/123456-2024"


def test_aop_parse_table(make_source, payload_factory):
    src = make_source(AopSource, base_url="https://www.aop.bg")
    payload = payload_factory("aop_sample.html", "https://www.aop.bg/list", ext="html")
    records = list(src.parse(payload))

    assert len(records) == 2
    r = records[0]
    assert r.natural_key == "00038-2023-0012"
    assert r.payload["row"]["Възложител"] == "Община Сливен"
    assert r.source_url == "https://www.aop.bg/case?id=00038-2023-0012"


def test_eop_parse(make_source, payload_factory):
    src = make_source(EopSource, base_url="https://app.eop.bg")
    payload = payload_factory("eop_sample.html", "https://app.eop.bg/search", ext="html")
    records = list(src.parse(payload))
    assert len(records) >= 2
    assert any("Община Русе" in r.payload["text"] for r in records)


def test_isun_parse(make_source, payload_factory):
    src = make_source(IsunSource, base_url="https://2020.eufunds.bg")
    payload = payload_factory("isun_sample.html", "https://2020.eufunds.bg/list", ext="html")
    records = list(src.parse(payload))
    beneficiaries = [r.payload["beneficiary"] for r in records if r.payload.get("beneficiary")]
    assert "Иновейшън ЕООД" in beneficiaries
    grants = [r.payload["grant"]["amount"] for r in records
              if r.payload["grant"]["amount"]]
    assert 1200000.0 in grants


def test_sebra_parse(make_source, payload_factory):
    src = make_source(SebraSource, base_url="https://minfin.bg")
    payload = payload_factory("sebra_sample.csv", "https://minfin.bg/x.csv", ext="csv")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert records[0].payload["spender"] == "Министерство на здравеопазването"
    assert records[0].payload["amount"]["value"] == 120000.50
    assert records[0].payload["paid_at"].startswith("2024-03-31")
