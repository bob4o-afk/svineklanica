from __future__ import annotations

from analyzer.agents.donation_influence import signals as donation_signals
from analyzer.context import context_from_records
from analyzer.features.donations import extract as donations_extract
from analyzer.llm import NullClient
from analyzer.orchestrator import analyze_view
from analyzer.payload import view_from_record
from analyzer.routing import resolve_sphere, route_flow
from analyzer.schemas import DonationInfluenceOutput
from analyzer.spheres import (
    CAT_DONATIONS,
    CAT_JOBS,
    FLOW_ASSETS,
    FLOW_DONATIONS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
    SPHERE_POLICE,
)

from conftest import StubClient, make_record


def test_resolve_sphere_from_mvr_source():
    rec = make_record(source="mvr", natural_key="1", title="Доставка на гориво")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_POLICE


def test_resolve_sphere_from_payload():
    rec = make_record(source="mvr", natural_key="2", sphere=SPHERE_POLICE)
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_POLICE


def test_route_mvr_to_procurement():
    rec = make_record(source="mvr", natural_key="P1", title="Доставка на облекло", sphere=SPHERE_POLICE)
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROCUREMENT


def test_route_mvr_jobs_to_jobs():
    rec = make_record(
        source="mvr_jobs",
        natural_key="J1",
        title="Конкурс за началник на отдел",
        category="конкурси за работа",
        sphere=SPHERE_POLICE,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_JOBS


def test_route_mvr_assets_to_assets():
    rec = make_record(
        source="mvr_assets",
        natural_key="A1",
        title="Търг за продажба на служебен автомобил",
        type="asset_disposal",
        sphere=SPHERE_POLICE,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_ASSETS


def test_route_mvr_donations_to_donations():
    rec = make_record(
        source="mvr_donations",
        natural_key="D1",
        donor="Аксиом ООД",
        subject="Автомобил",
        value={"amount": None, "currency": "BGN"},
        donated_at="2026-02-15T00:00:00Z",
        category="нерегламентирани плащания",
        sphere=SPHERE_POLICE,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_DONATIONS


def test_donations_feature_in_kind():
    rec = make_record(
        source="mvr_donations",
        natural_key="D2",
        donor="Тест ЕООД",
        subject="Оборудване",
        value={"amount": None, "currency": "BGN"},
        sphere=SPHERE_POLICE,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    signals = donations_extract(view, ctx)
    assert any(s.key == "in_kind_donation" for s in signals)


def test_donations_feature_repeat_donor():
    rec1 = make_record(
        source="mvr_donations",
        natural_key="D3a",
        donor="Повтарящ се АД",
        subject="Средства",
        value={"amount": 10000, "currency": "BGN"},
        sphere=SPHERE_POLICE,
    )
    rec2 = make_record(
        source="mvr_donations",
        natural_key="D3b",
        donor="Повтарящ се АД",
        subject="Техника",
        value={"amount": 5000, "currency": "BGN"},
        sphere=SPHERE_POLICE,
    )
    ctx = context_from_records([rec1, rec2])
    view = ctx.views[0]
    signals = donations_extract(view, ctx)
    assert any(s.key == "repeat_donor" for s in signals)


def test_donations_feature_large_donation():
    rec = make_record(
        source="mvr_donations",
        natural_key="D4",
        donor="Голям дарител",
        subject="Средства",
        value={"amount": 75000, "currency": "BGN"},
        sphere=SPHERE_POLICE,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    signals = donations_extract(view, ctx)
    assert any(s.key == "large_donation" for s in signals)


def test_police_jobs_verdict_has_sphere_category():
    rec = make_record(
        source="mvr_jobs",
        natural_key="JOB1",
        title="Конкурс за инспектор",
        category="конкурси за работа",
        sphere=SPHERE_POLICE,
        published_at="2026-01-30T00:00:00Z",
        deadline="2026-02-10T00:00:00Z",
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_JOBS)
    assert verdict.sphere == SPHERE_POLICE
    assert verdict.category == CAT_JOBS
    assert verdict.flow_key == FLOW_JOBS


def test_donation_influence_llm_signal():
    out = DonationInfluenceOutput(influence_suspicion=0.88, rationale_bg="Дарител-доставчик.")
    rec = make_record(source="mvr_donations", natural_key="D5", donor="Тест")
    view = view_from_record(rec)
    sigs = donation_signals(out, view)
    assert sigs[0].key == "donation_influence_llm"
    assert sigs[0].family == "donations"


def test_police_donations_hard_trip():
    rec1 = make_record(
        source="mvr_donations",
        natural_key="D6a",
        donor="Repeat Corp",
        subject="Средства",
        value={"amount": 60000, "currency": "BGN"},
        sphere=SPHERE_POLICE,
    )
    rec2 = make_record(
        source="mvr_donations",
        natural_key="D6b",
        donor="Repeat Corp",
        subject="Оборудване",
        value={"amount": 20000, "currency": "BGN"},
        sphere=SPHERE_POLICE,
    )
    ctx = context_from_records([rec1, rec2])
    view = ctx.views[0]
    stub = StubClient(
        {
            DonationInfluenceOutput: DonationInfluenceOutput(
                influence_suspicion=0.9,
                repeat_donor=True,
                rationale_bg="Повтарящ се дарител-доставчик.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_DONATIONS)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_DONATIONS
