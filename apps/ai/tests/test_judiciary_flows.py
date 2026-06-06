from __future__ import annotations

from analyzer.agents.magistrate_competition import signals as magistrate_signals
from analyzer.agents.unexplained_wealth import signals as wealth_signals
from analyzer.context import context_from_records
from analyzer.features.declarations import extract as declarations_extract
from analyzer.llm import NullClient
from analyzer.orchestrator import analyze_view
from analyzer.payload import view_from_record
from analyzer.routing import resolve_sphere, route_flow
from analyzer.schemas import MagistrateCompetitionOutput, UnexplainedWealthOutput
from analyzer.spheres import (
    CAT_DECLARATIONS,
    CAT_JOBS,
    FLOW_ASSETS,
    FLOW_DECLARATIONS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
    SPHERE_JUDICIARY,
)

from conftest import StubClient, make_record


def test_resolve_sphere_from_payload():
    rec = make_record(source="vss", natural_key="1", sphere=SPHERE_JUDICIARY)
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_JUDICIARY


def test_route_vss_to_procurement():
    rec = make_record(source="vss", natural_key="V1", title="Доставка на гориво", sphere=SPHERE_JUDICIARY)
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROCUREMENT


def test_route_vss_jobs_to_jobs():
    rec = make_record(
        source="vss_jobs",
        natural_key="J1",
        title="Конкурс за младши съдии 2026",
        category="конкурси за работа",
        sphere=SPHERE_JUDICIARY,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_JOBS


def test_route_ivss_to_declarations():
    rec = make_record(
        source="ivss_declarations",
        natural_key="D1",
        magistrate="Иван Петров",
        position="Съдия",
        court="Окръжен съд София",
        declared_at="2026-02-15T00:00:00Z",
        category="нерегламентирани плащания",
        sphere=SPHERE_JUDICIARY,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_DECLARATIONS


def test_route_mjs_assets_to_assets():
    rec = make_record(
        source="mjs_assets",
        natural_key="A1",
        title="Търг за продажба на сграда на районен съд",
        type="asset_disposal",
        sphere=SPHERE_JUDICIARY,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_ASSETS


def test_declarations_feature_late_filing():
    rec = make_record(
        source="ivss_declarations",
        natural_key="D2",
        magistrate="Мария Георгиева",
        position="Прокурор",
        court="Районна прокуратура",
        declared_at="2026-06-01T00:00:00Z",
        sphere=SPHERE_JUDICIARY,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    signals = declarations_extract(view, ctx)
    assert any(s.key == "late_declaration" for s in signals)


def test_judiciary_jobs_verdict_has_sphere_category():
    rec = make_record(
        source="vss_jobs",
        natural_key="JOB1",
        title="Конкурс за младши прокурори",
        category="конкурси за работа",
        sphere=SPHERE_JUDICIARY,
        published_at="2026-01-30T00:00:00Z",
        deadline="2026-02-10T00:00:00Z",
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_JOBS)
    assert verdict.sphere == SPHERE_JUDICIARY
    assert verdict.category == CAT_JOBS
    assert verdict.flow_key == FLOW_JOBS


def test_magistrate_competition_llm_signal():
    out = MagistrateCompetitionOutput(rigging_confidence=0.9, rushed_procedure=True, rationale_bg="Бърза процедура.")
    rec = make_record(source="vss_jobs", natural_key="J2", title="Конкурс")
    view = view_from_record(rec)
    sigs = magistrate_signals(out, view)
    assert sigs[0].key == "magistrate_competition_llm"
    assert sigs[0].family == "jobs"


def test_unexplained_wealth_llm_signal():
    out = UnexplainedWealthOutput(wealth_vs_income_suspicion=0.88, rationale_bg="Несъразмерно имущество.")
    rec = make_record(source="ivss_declarations", natural_key="D3", magistrate="Тест")
    view = view_from_record(rec)
    sigs = wealth_signals(out, view)
    assert sigs[0].key == "unexplained_wealth_llm"
    assert sigs[0].family == "wealth"


def test_judiciary_declarations_hard_trip():
    rec = make_record(
        source="ivss_declarations",
        natural_key="D4",
        magistrate="Петър Иванов",
        position="Съдия",
        court="Окръжен съд",
        declared_at="2026-06-01T00:00:00Z",
        sphere=SPHERE_JUDICIARY,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    stub = StubClient(
        {
            UnexplainedWealthOutput: UnexplainedWealthOutput(
                wealth_vs_income_suspicion=0.9,
                late_or_missing_declaration=True,
                rationale_bg="Късно и необяснимо.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_DECLARATIONS)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_DECLARATIONS
