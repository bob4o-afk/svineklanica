"""Offline parser tests — each source's ``parse`` against a committed fixture."""

from __future__ import annotations

from scraper.sources.aop import AopSource
from scraper.sources.caiseop import CaiseopSource
from scraper.sources.egov import EgovSource
from scraper.sources.eop import EopSource
from scraper.sources.api_jobs import ApiJobsSource
from scraper.sources.api_projects import ApiProjectsSource
from scraper.sources.api_tenders import ApiTendersSource
from scraper.sources.avtomagistrali_tenders import AvtomagistraliTendersSource
from scraper.sources.gov_audits import GovAuditsSource
from scraper.sources.gov_concessions import GovConcessionsSource
from scraper.sources.gov_declarations import GovDeclarationsSource
from scraper.sources.gov_jobs import GovJobsSource
from scraper.sources.gov_tenders import GovTendersSource
from scraper.sources.isun import IsunSource
from scraper.sources.mrrb_tenders import MrrbTendersSource
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
    assert first.payload["record_type"] == "tender"
    assert first.payload["title"] == "Доставка на преносими компютри"
    assert first.payload["tender"]["value"] == 120000.50
    assert first.payload["tender"]["currency"] == "BGN"
    assert first.payload["tender"]["cpv_code"] == "30213100"
    assert first.payload["winner"]["eik"] == "100000026"
    assert first.payload["winner_eik_valid"] is True
    assert first.payload["tender"]["awarded_at"].startswith("2024-03-15")

    # row 2 has a bogus winner EIK -> flagged invalid, not dropped
    assert records[1].payload["winner"]["eik"] == "999999999"
    assert records[1].payload["winner_eik_valid"] is False


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
    assert r.payload["record_type"] == "tender"
    assert r.payload["category"] == "обществена поръчка"
    assert r.payload["title"] == "Доставка на медицинско оборудване"
    assert r.payload["authority"]["name"] == "УМБАЛ Бургас"
    assert r.payload["tender"]["value"] == 2500000.0
    assert r.payload["tender"]["cpv_code"] == "33100000"
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
    assert records[0].payload["record_type"] == "payment"
    assert records[0].payload["category"] == "нерегламентирани плащания"
    assert records[0].payload["payment"]["spender"] == "Министерство на здравеопазването"
    assert records[0].payload["payment"]["amount"] == 120000.50
    assert records[0].payload["payment"]["paid_at"].startswith("2024-03-31")


def test_gov_tenders_parse(make_source, payload_factory):
    src = make_source(GovTendersSource, base_url="https://www.government.bg")
    payload = payload_factory("gov_tenders.html", "https://www.government.bg/bg/profil-na-kupuvacha", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "канцеларски материали" in records[0].payload["title"]
    assert records[0].payload["buyer"] == "МИНИСТЕРСКИ СЪВЕТ"
    assert records[0].payload["published_at"].startswith("2026-06-01")


def test_gov_jobs_parse(make_source, payload_factory):
    src = make_source(GovJobsSource, base_url="https://iisda.government.bg")
    payload = payload_factory("gov_jobs.html", "https://iisda.government.bg/competitions", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "началник отдел" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-10")


def test_gov_audits_parse(make_source, payload_factory):
    src = make_source(GovAuditsSource, base_url="https://www.bulnao.government.bg")
    payload = payload_factory("gov_audits.html", "https://www.bulnao.government.bg/bg/oditni-dokladi/", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "Община Варна" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-05-20")


def test_gov_declarations_parse(make_source, payload_factory):
    src = make_source(GovDeclarationsSource, base_url="https://register.antikorupcia.bg")
    payload = payload_factory("gov_declarations.html", "https://register.antikorupcia.bg/", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert records[0].payload["official_name"] == "Бойко Борисов"
    assert records[0].payload["position"] == "Народен представител"
    assert records[0].payload["declared_at"].startswith("2026-05-15")


def test_gov_concessions_parse(make_source, payload_factory):
    src = make_source(GovConcessionsSource, base_url="https://nkr.government.bg")
    payload = payload_factory("gov_concessions.html", "https://nkr.government.bg/", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "Скалата" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-05")


def test_api_tenders_parse(make_source, payload_factory):
    src = make_source(ApiTendersSource, base_url="https://www.api.bg")
    payload = payload_factory("api_tenders.html", "https://www.api.bg/bg/profil-na-kupuvacha", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "републикански пътища" in records[0].payload["title"]
    assert records[0].payload["buyer"] == "АГЕНЦИЯ ПЪТНА ИНФРАСТРУКТУРА"


def test_api_jobs_parse(make_source, payload_factory):
    src = make_source(ApiJobsSource, base_url="https://www.api.bg")
    payload = payload_factory("api_jobs.html", "https://www.api.bg/bg/konkursi", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "Благоевград" in records[0].payload["title"]


def test_api_projects_parse(make_source, payload_factory):
    src = make_source(ApiProjectsSource, base_url="https://www.api.bg")
    payload = payload_factory("api_projects.html", "https://www.api.bg/bg/proekti", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 2
    assert "Хемус" in records[0].payload["title"]


def test_mrrb_tenders_parse(make_source, payload_factory):
    src = make_source(MrrbTendersSource, base_url="https://www.mrrb.bg")
    payload = payload_factory("mrrb_tenders.html", "https://www.mrrb.bg/bg/profil-na-kupuvacha/", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 1
    assert "свлачища" in records[0].payload["title"]


def test_avtomagistrali_tenders_parse(make_source, payload_factory):
    src = make_source(AvtomagistraliTendersSource, base_url="https://avtomagistrali.com")
    payload = payload_factory("avtomagistrali_tenders.html", "https://avtomagistrali.com/bg/profil-na-kupuvacha", ext="html")
    records = list(src.parse(payload))
    assert len(records) == 1
    assert "строителна техника" in records[0].payload["title"]
