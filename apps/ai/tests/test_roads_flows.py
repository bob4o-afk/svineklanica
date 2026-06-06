from __future__ import annotations

from analyzer.agents.project_abuse import signals as project_abuse_signals
from analyzer.context import context_from_records
from analyzer.features.projects import extract as projects_extract
from analyzer.llm import NullClient
from analyzer.orchestrator import analyze_view
from analyzer.payload import view_from_record
from analyzer.routing import resolve_sphere, route_flow
from analyzer.schemas import ProjectAbuseOutput, ScopeRealismOutput
from analyzer.spheres import (
    CAT_JOBS,
    CAT_PROCUREMENT,
    CAT_PROJECTS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
    FLOW_PROJECTS,
    SPHERE_ROADS,
)

from conftest import StubClient, make_record


def test_resolve_sphere_from_api_tenders():
    rec = make_record(source="api_tenders", natural_key="1", title="Ремонт на пътища")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_ROADS


def test_resolve_sphere_from_mrrb_tenders():
    rec = make_record(source="mrrb_tenders", natural_key="2", title="Поръчка МРРБ")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_ROADS


def test_resolve_sphere_from_avtomagistrali_tenders():
    rec = make_record(source="avtomagistrali_tenders", natural_key="3", title="Търг АПИ")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_ROADS


def test_resolve_sphere_from_api_jobs():
    rec = make_record(source="api_jobs", natural_key="4", title="Конкурс АПИ")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_ROADS


def test_resolve_sphere_from_api_projects():
    rec = make_record(source="api_projects", natural_key="5", title="Хемус участък 1")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_ROADS


def test_resolve_sphere_from_payload():
    rec = make_record(source="api_tenders", natural_key="6", sphere=SPHERE_ROADS)
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_ROADS


def test_route_api_tenders_to_procurement():
    rec = make_record(
        source="api_tenders",
        natural_key="P1",
        title="Текущ ремонт и поддръжка на републикански пътища",
        buyer="АГЕНЦИЯ ПЪТНА ИНФРАСТРУКТУРА",
        sphere=SPHERE_ROADS,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROCUREMENT


def test_route_api_jobs_to_jobs():
    rec = make_record(
        source="api_jobs",
        natural_key="J1",
        title="Конкурс за началник на отдел",
        category="конкурси за работа",
        sphere=SPHERE_ROADS,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_JOBS


def test_route_api_projects_to_projects():
    rec = make_record(
        source="api_projects",
        natural_key="PR1",
        title='Автомагистрала "Хемус" - участък 1. Статус: В строителство.',
        category="инфраструктурни проекти",
        sphere=SPHERE_ROADS,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROJECTS


def test_route_ted_roads_cpv_to_procurement():
    rec = make_record(
        source="ted",
        natural_key="T-R1",
        cpv="45233120-1",
        title="Строителство на пътна инфраструктура",
        sphere=SPHERE_ROADS,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROCUREMENT


def test_stalled_project_keywords():
    rec = make_record(
        source="api_projects",
        natural_key="PR2",
        title='Автомагистрала "Хемус" - участък 2. Статус: Замразен.',
        sphere=SPHERE_ROADS,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    signals = projects_extract(view, ctx)
    assert any(s.key == "stalled_project_keywords" for s in signals)


def test_repeat_project_entity():
    rec1 = make_record(
        source="api_projects",
        natural_key="PR3a",
        title='Автомагистрала "Хемус" - участък 1. Статус: В строителство.',
        sphere=SPHERE_ROADS,
    )
    rec2 = make_record(
        source="api_projects",
        natural_key="PR3b",
        title='Автомагистрала "Хемус" - участък 1. Статус: В строителство.',
        sphere=SPHERE_ROADS,
    )
    ctx = context_from_records([rec1, rec2])
    view = ctx.views[0]
    signals = projects_extract(view, ctx)
    assert any(s.key == "repeat_project_entity" for s in signals)


def test_project_abuse_llm_signal():
    out = ProjectAbuseOutput(abuse_confidence=0.88, rationale_bg="Забавен участък.")
    rec = make_record(source="api_projects", natural_key="PR4", title="Хемус")
    view = view_from_record(rec)
    sigs = project_abuse_signals(out, view)
    assert sigs[0].key == "project_abuse_llm"
    assert sigs[0].family == "projects"


def test_projects_hard_trip():
    rec1 = make_record(
        source="api_projects",
        natural_key="PR5a",
        title='Автомагистрала "Хемус" - участък 1. Статус: В строителство.',
        sphere=SPHERE_ROADS,
    )
    rec2 = make_record(
        source="api_projects",
        natural_key="PR5b",
        title='Автомагистрала "Хемус" - участък 1. Статус: В строителство.',
        sphere=SPHERE_ROADS,
    )
    ctx = context_from_records([rec1, rec2])
    view = ctx.views[0]
    stub = StubClient(
        {
            ProjectAbuseOutput: ProjectAbuseOutput(
                abuse_confidence=0.9,
                delay_pattern=True,
                rationale_bg="Повтарящ се застинал проект.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_PROJECTS)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_PROJECTS


def test_procurement_hard_trip_scope_single_bidder():
    rec = make_record(
        source="api_tenders",
        natural_key="P2",
        title="Текущ ремонт на нов асфалт",
        bids_count=1,
        sphere=SPHERE_ROADS,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    stub = StubClient(
        {
            ScopeRealismOutput: ScopeRealismOutput(
                scope_implausibility=0.9,
                rationale_bg="Ремонт на нов път — нереалистичен обхват.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_PROCUREMENT)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_PROCUREMENT


def test_roads_jobs_verdict_has_sphere_category():
    rec = make_record(
        source="api_jobs",
        natural_key="JOB1",
        title="Конкурс за експерт",
        category="конкурси за работа",
        sphere=SPHERE_ROADS,
        published_at="2026-01-30T00:00:00Z",
        deadline="2026-02-10T00:00:00Z",
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_JOBS)
    assert verdict.sphere == SPHERE_ROADS
    assert verdict.category == CAT_JOBS
    assert verdict.flow_key == FLOW_JOBS


def test_roads_projects_verdict_category():
    rec = make_record(
        source="api_projects",
        natural_key="PR6",
        title='Автомагистрала "Хемус" - участък 1',
        sphere=SPHERE_ROADS,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_PROJECTS)
    assert verdict.sphere == SPHERE_ROADS
    assert verdict.category == CAT_PROJECTS
