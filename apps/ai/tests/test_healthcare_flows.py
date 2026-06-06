from __future__ import annotations

from analyzer.agents.rigged_competition import signals as rigged_signals
from analyzer.agents.drug_overpricing import signals as drug_signals
from analyzer.context import context_from_records
from analyzer.features.drug_pricing import extract as drug_extract
from analyzer.llm import NullClient
from analyzer.orchestrator import analyze_view
from analyzer.schemas import DrugOverpricingOutput, RiggedCompetitionOutput
from analyzer.spheres import CAT_JOBS, FLOW_JOBS, SPHERE_HEALTHCARE

from conftest import StubClient, make_record


def test_drug_index_built_from_ncpr():
    ncpr = make_record(
        source="ncpr",
        natural_key="n1",
        inn="Paracetamol",
        product="Парацетамол 500mg",
        price_ceiling={"amount": 10.0, "currency": "BGN"},
        price_reimbursement={"amount": 8.0, "currency": "BGN"},
        holder="ФАРМА АД",
    )
    tender = make_record(
        source="nhif",
        natural_key="t1",
        title="Доставка на Парацетамол 500mg",
        product="Парацетамол 500mg",
        value={"amount": 25.0, "currency": "BGN"},
        sphere=SPHERE_HEALTHCARE,
    )
    ctx = context_from_records([ncpr, tender])
    assert "парацетамол 500mg" in ctx.drug_index
    view = next(v for v in ctx.views if v.natural_key == "t1")
    signals = drug_extract(view, ctx)
    assert any(s.key == "drug_above_ceiling" for s in signals)


def test_healthcare_jobs_verdict_has_sphere_category():
    rec = make_record(
        source="mz_jobs",
        natural_key="JOB1",
        title="Конкурс за директор на болница",
        category="конкурси за работа",
        sphere=SPHERE_HEALTHCARE,
        published_at="2026-06-01T00:00:00Z",
        deadline="2026-06-05T00:00:00Z",
    )
    ctx = context_from_records([rec])
    view = ctx.views[0]
    verdict = analyze_view(view, ctx, NullClient(), model_name="none", flow_key=FLOW_JOBS)
    assert verdict.sphere == SPHERE_HEALTHCARE
    assert verdict.category == CAT_JOBS
    assert verdict.flow_key == FLOW_JOBS


def test_rigged_competition_llm_signal():
    out = RiggedCompetitionOutput(rigging_confidence=0.9, short_deadline=True, rationale_bg="Кратък срок.")
    rec = make_record(source="mz_jobs", natural_key="J2", title="Конкурс")
    from analyzer.payload import view_from_record

    view = view_from_record(rec)
    sigs = rigged_signals(out, view)
    assert sigs[0].key == "rigged_competition_llm"
    assert sigs[0].family == "jobs"


def test_drug_overpricing_llm_signal():
    out = DrugOverpricingOutput(overpricing_confidence=0.85, markup_ratio=1.5, rationale_bg="Завишена цена.")
    rec = make_record(source="nhif", natural_key="D2", title="Лекарства")
    from analyzer.payload import view_from_record

    view = view_from_record(rec)
    sigs = drug_signals(out, view)
    assert sigs[0].key == "drug_overpricing_llm"


def test_drug_hard_trip():
    ncpr = make_record(
        source="ncpr",
        natural_key="n2",
        product="Ибупрофен 400mg",
        price_ceiling={"amount": 5.0, "currency": "BGN"},
    )
    tender = make_record(
        source="nhif",
        natural_key="t2",
        title="Ибупрофен 400mg",
        product="Ибупрофен 400mg",
        value={"amount": 20.0, "currency": "BGN"},
        sphere=SPHERE_HEALTHCARE,
    )
    ctx = context_from_records([ncpr, tender])
    view = next(v for v in ctx.views if v.natural_key == "t2")
    stub = StubClient({DrugOverpricingOutput: DrugOverpricingOutput(overpricing_confidence=0.8, rationale_bg="Да.")})
    verdict = analyze_view(view, ctx, stub, model_name="stub", flow_key="drugs")
    assert verdict.hard_tripped is True
    assert verdict.corruption_score >= 99
