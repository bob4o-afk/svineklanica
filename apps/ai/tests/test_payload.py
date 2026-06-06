from __future__ import annotations

from analyzer.payload import view_from_record

from conftest import make_record


def test_ted_like_payload_maps_core_fields():
    rec = make_record(
        source="ted",
        natural_key="387269-2026",
        title="Проектиране на Северен обходен път",
        buyer="ОБЩИНА ГОРНА ОРЯХОВИЦА",
        value={"amount": 115673.72, "currency": "BGN"},
        cpv="71200000",
    )
    v = view_from_record(rec)
    assert v.title.startswith("Проектиране")
    assert v.buyer_name == "ОБЩИНА ГОРНА ОРЯХОВИЦА"
    assert v.value_amount == 115673.72
    assert v.cpv == "71200000"
    assert v.cpv_division == "71"
    assert v.source_url.endswith("387269-2026")


def test_rich_payload_with_entities_and_bidders():
    rec = make_record(
        source="caiseop",
        subject="Доставка на компютри",
        authority={"name": "МЗ", "eik": "000695317"},
        winner={"name": "ФИРМА ЕООД", "eik": "131071587"},
        value={"amount": 50000, "currency": "BGN"},
        bidders=[
            {"name": "ФИРМА ЕООД", "amount": 50000},
            {"name": "ДРУГА ООД", "amount": 50000, "status": "rejected"},
        ],
    )
    v = view_from_record(rec)
    assert v.buyer_name == "МЗ"
    assert v.winner_name == "ФИРМА ЕООД"
    assert v.bids_count == 2
    assert len(v.bidders) == 2
    assert v.bidders[1].disqualified is True
