from __future__ import annotations

from analyzer import features
from analyzer.context import context_from_records

from conftest import make_record


def _signals_for(record, corpus=None):
    corpus = corpus or [record]
    ctx = context_from_records(corpus)
    view = next(v for v in ctx.views if v.natural_key == record["natural_key"])
    return {s.key: s for s in features.extract_all(view, ctx)}


def test_single_bidder_flagged():
    rec = make_record(natural_key="SB", bids_count=1)
    sigs = _signals_for(rec)
    assert "single_bidder" in sigs
    assert sigs["single_bidder"].risk >= 0.8


def test_non_open_procedure_detected():
    rec = make_record(natural_key="NO", procedure_type="пряко договаряне без предварително обявление")
    sigs = _signals_for(rec)
    assert "non_open_procedure" in sigs


def test_just_under_threshold():
    rec = make_record(natural_key="THR", value={"amount": 69000, "currency": "BGN"})
    sigs = _signals_for(rec)
    assert "just_under_threshold" in sigs
    assert sigs["just_under_threshold"].risk > 0.4


def test_price_outlier_vs_cpv_peers():
    corpus = [
        make_record(natural_key=f"N{i}", cpv="45000000", value={"amount": 100000 + i * 2500})
        for i in range(8)
    ]
    outlier = make_record(natural_key="OUT", cpv="45000000", value={"amount": 5_000_000})
    corpus.append(outlier)
    sigs = _signals_for(outlier, corpus)
    assert "price_outlier" in sigs


def test_serial_winner_across_corpus():
    corpus = [
        make_record(
            natural_key=f"W{i}",
            winner={"name": "МОНОПОЛ ЕООД", "eik": "131071587"},
            buyer="ОБЩИНА Х",
        )
        for i in range(6)
    ]
    sigs = _signals_for(corpus[0], corpus)
    assert "serial_winner" in sigs
    assert "buyer_dependence" in sigs


def test_identical_bid_prices_collusion():
    rec = make_record(
        natural_key="COL",
        winner={"name": "А ЕООД"},
        bidders=[
            {"name": "А ЕООД", "amount": 99999},
            {"name": "Б ООД", "amount": 99999},
        ],
    )
    sigs = _signals_for(rec)
    assert "identical_bid_prices" in sigs


def test_clean_record_has_no_signals():
    rec = make_record(natural_key="CLEAN", title="Доставка на хартия", value={"amount": 4200})
    sigs = _signals_for(rec)
    assert sigs == {}
