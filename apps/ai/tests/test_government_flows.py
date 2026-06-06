from __future__ import annotations

from analyzer.agents.audit_findings import signals as audit_signals
from analyzer.agents.concession_abuse import signals as concession_signals
from analyzer.agents.gov_official_wealth import signals as gov_wealth_signals
from analyzer.context import context_from_records
from analyzer.features.audits import extract as audits_extract
from analyzer.features.concessions import extract as concessions_extract
from analyzer.features.gov_declarations import extract as gov_declarations_extract
from analyzer.llm import NullClient
from analyzer.orchestrator import analyze_view
from analyzer.payload import view_from_record
from analyzer.routing import resolve_sphere, route_flow
from analyzer.schemas import AuditFindingsOutput, ConcessionAbuseOutput, GovOfficialWealthOutput
from analyzer.spheres import (
    CAT_AUDITS,
    CAT_CONCESSIONS,
    CAT_GOV_DECLARATIONS,
    CAT_JOBS,
    CAT_PROCUREMENT,
    FLOW_AUDITS,
    FLOW_CONCESSIONS,
    FLOW_GOV_DECLARATIONS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
    SPHERE_GOVERNMENT,
)

from conftest import StubClient, make_record


def test_resolve_sphere_from_gov_source():
    rec = make_record(source="gov_tenders", natural_key="1", title="Доставка за МС")
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_GOVERNMENT


def test_resolve_sphere_from_payload():
    rec = make_record(source="gov_tenders", natural_key="2", sphere=SPHERE_GOVERNMENT)
    view = view_from_record(rec)
    assert resolve_sphere(view) == SPHERE_GOVERNMENT


def test_route_gov_tenders_to_procurement():
    rec = make_record(
        source="gov_tenders",
        natural_key="P1",
        title="Доставка на оборудване",
        sphere=SPHERE_GOVERNMENT,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_PROCUREMENT


def test_route_gov_jobs_to_jobs():
    rec = make_record(
        source="gov_jobs",
        natural_key="J1",
        title="Конкурс за директор",
        category="конкурси за работа",
        sphere=SPHERE_GOVERNMENT,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_JOBS


def test_route_gov_audits_to_audits():
    rec = make_record(
        source="gov_audits",
        natural_key="A1",
        title="Одитен доклад за министерство",
        category="одити",
        sphere=SPHERE_GOVERNMENT,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_AUDITS


def test_route_gov_declarations_to_gov_declarations():
    rec = make_record(
        source="gov_declarations",
        natural_key="D1",
        official_name="Иван Иванов",
        position="Министър",
        institution="МС",
        declared_at="2025-06-01T00:00:00Z",
        category="имуществени декларации",
        sphere=SPHERE_GOVERNMENT,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_GOV_DECLARATIONS


def test_route_gov_concessions_to_concessions():
    rec = make_record(
        source="gov_concessions",
        natural_key="C1",
        title="Концесия за автомагистрала",
        category="концесии",
        sphere=SPHERE_GOVERNMENT,
    )
    view = view_from_record(rec)
    assert route_flow(view, None) == FLOW_CONCESSIONS


def test_gov_declarations_late_declaration():
    rec = make_record(
        source="gov_declarations",
        natural_key="D2",
        official_name="Петър Петров",
        position="Зам.-министър",
        institution="МФ",
        declared_at="2026-06-01T00:00:00Z",
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    signals = gov_declarations_extract(view, ctx)
    assert any(s.key == "late_declaration" for s in signals)


def test_audits_repeat_entity():
    rec1 = make_record(
        source="gov_audits",
        natural_key="A2a",
        title="Одит на Министерство на финансите",
        buyer_name="Министерство на финансите",
        sphere=SPHERE_GOVERNMENT,
    )
    rec2 = make_record(
        source="gov_audits",
        natural_key="A2b",
        title="Одит на Министерство на финансите",
        buyer_name="Министерство на финансите",
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec1, rec2])
    view = ctx.views[0]
    signals = audits_extract(view, ctx)
    assert any(s.key == "repeat_audited_entity" for s in signals)


def test_concessions_single_bidder():
    rec = make_record(
        source="gov_concessions",
        natural_key="C2",
        title="Концесия за пристанище",
        bids_count=1,
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    signals = concessions_extract(view, ctx)
    assert any(s.key == "single_bidder" for s in signals)


def test_gov_jobs_verdict_has_sphere_category():
    rec = make_record(
        source="gov_jobs",
        natural_key="JOB1",
        title="Конкурс за експерт",
        category="конкурси за работа",
        sphere=SPHERE_GOVERNMENT,
        published_at="2026-01-30T00:00:00Z",
        deadline="2026-02-10T00:00:00Z",
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_JOBS)
    assert verdict.sphere == SPHERE_GOVERNMENT
    assert verdict.category == CAT_JOBS
    assert verdict.flow_key == FLOW_JOBS


def test_audit_findings_llm_signal():
    out = AuditFindingsOutput(findings_severity=0.88, rationale_bg="Системни нарушения.")
    rec = make_record(source="gov_audits", natural_key="A3", title="Одит")
    view = view_from_record(rec)
    sigs = audit_signals(out, view)
    assert sigs[0].key == "audit_findings_llm"
    assert sigs[0].family == "audits"


def test_gov_official_wealth_llm_signal():
    out = GovOfficialWealthOutput(wealth_vs_income_suspicion=0.75, rationale_bg="Несъразмерност.")
    rec = make_record(source="gov_declarations", natural_key="D3", official_name="Тест")
    view = view_from_record(rec)
    sigs = gov_wealth_signals(out, view)
    assert sigs[0].key == "gov_official_wealth_llm"
    assert sigs[0].family == "gov_wealth"


def test_concession_abuse_llm_signal():
    out = ConcessionAbuseOutput(abuse_confidence=0.8, rationale_bg="Lock-in.")
    rec = make_record(source="gov_concessions", natural_key="C3", title="Концесия")
    view = view_from_record(rec)
    sigs = concession_signals(out, view)
    assert sigs[0].key == "concession_abuse_llm"
    assert sigs[0].family == "concessions"


def test_gov_audits_hard_trip():
    rec1 = make_record(
        source="gov_audits",
        natural_key="A4a",
        title="Одит на Агенция X",
        buyer_name="Агенция X",
        sphere=SPHERE_GOVERNMENT,
    )
    rec2 = make_record(
        source="gov_audits",
        natural_key="A4b",
        title="Одит на Агенция X",
        buyer_name="Агенция X",
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec1, rec2])
    view = ctx.views[0]
    stub = StubClient(
        {
            AuditFindingsOutput: AuditFindingsOutput(
                findings_severity=0.9,
                repeat_target=True,
                rationale_bg="Повтарящи се тежки констатации.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_AUDITS)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_AUDITS


def test_gov_concessions_hard_trip():
    rec = make_record(
        source="gov_concessions",
        natural_key="C4",
        title="Концесия",
        bids_count=1,
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    stub = StubClient(
        {
            ConcessionAbuseOutput: ConcessionAbuseOutput(
                abuse_confidence=0.9,
                operator_lock_in=True,
                rationale_bg="Заключване на оператор.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_CONCESSIONS)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_CONCESSIONS


def test_gov_declarations_hard_trip():
    rec = make_record(
        source="gov_declarations",
        natural_key="D4",
        official_name="Тест Тестов",
        position="Министър",
        institution="МС",
        declared_at="2026-06-15T00:00:00Z",
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    stub = StubClient(
        {
            GovOfficialWealthOutput: GovOfficialWealthOutput(
                wealth_vs_income_suspicion=0.9,
                rationale_bg="Необяснимо имущество.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key=FLOW_GOV_DECLARATIONS)
    assert verdict.hard_tripped is True
    assert verdict.category == CAT_GOV_DECLARATIONS


def test_gov_procurement_verdict_category():
    rec = make_record(
        source="gov_tenders",
        natural_key="P2",
        title="Обществена поръчка",
        sphere=SPHERE_GOVERNMENT,
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_PROCUREMENT)
    assert verdict.category == CAT_PROCUREMENT
