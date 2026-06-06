from __future__ import annotations

from analyzer.payload import view_from_record
from analyzer.schemas import LEVEL_CORRUPTION, LEVEL_NORMAL, Signal
from analyzer.scoring import score_record

from conftest import make_record


def _view(**payload):
    return view_from_record(make_record(**payload))


def _sig(key, family, risk):
    return Signal(key=key, family=family, risk=risk)


def test_hard_trip_conflict_of_interest_hits_100():
    view = _view(natural_key="HT1")
    sigs = [_sig("conflict_contact_overlap", "entities", 0.9)]
    score, level, hard, _scored, flags, reason = score_record(view, sigs)
    assert score == 100.0
    assert hard is True
    assert level == LEVEL_CORRUPTION
    assert reason


def test_hard_trip_single_bidder_plus_tailored_spec_hits_99():
    view = _view(natural_key="HT2")
    sigs = [_sig("single_bidder", "competition", 0.85), _sig("tailored_spec_llm", "specs", 0.8)]
    score, level, hard, *_ = score_record(view, sigs)
    assert score == 99.0
    assert hard is True


def test_clean_record_scores_zero():
    view = _view(natural_key="C")
    score, level, hard, *_ = score_record(view, [])
    assert score == 0.0
    assert level == LEVEL_NORMAL
    assert hard is False


def test_more_independent_families_raise_score():
    view = _view(natural_key="MONO")
    one = [_sig("price_outlier", "pricing", 0.6)]
    many = [
        _sig("price_outlier", "pricing", 0.6),
        _sig("non_open_procedure", "competition", 0.6),
        _sig("serial_winner", "entities", 0.6),
    ]
    s_one = score_record(view, one)[0]
    s_many = score_record(view, many)[0]
    assert 0 < s_one < s_many <= 100


def test_no_source_url_means_no_flags():
    rec = make_record(natural_key="NS", bids_count=1)
    rec["source_url"] = ""
    view = view_from_record(rec)
    sigs = [_sig("single_bidder", "competition", 0.85)]
    *_, flags, _ = score_record(view, sigs)
    assert flags == []


def test_flags_carry_source_url_and_type():
    view = _view(natural_key="F", winner={"name": "X ЕООД"})
    sigs = [_sig("single_bidder", "competition", 0.85)]
    *_, flags, _ = score_record(view, sigs)
    assert flags
    assert all(f.source_urls and f.source_urls[0] for f in flags)
