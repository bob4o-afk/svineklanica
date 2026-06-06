from __future__ import annotations

from analyzer.context import context_from_records
from analyzer.llm import NullClient
from analyzer.orchestrator import analyze_view
from analyzer.schemas import SpecRiggingOutput, SuspiciousCondition

from conftest import StubClient, make_record


def _view_and_ctx(record, corpus=None):
    ctx = context_from_records(corpus or [record])
    view = next(v for v in ctx.views if v.natural_key == record["natural_key"])
    return view, ctx


def test_deterministic_only_pipeline_produces_score_and_flags():
    rec = make_record(
        natural_key="DET",
        bids_count=1,
        procedure_type="пряко договаряне без предварително обявление",
        winner={"name": "ЕДИНСТВЕН ЕООД"},
    )
    view, ctx = _view_and_ctx(rec)
    verdict = analyze_view(view, ctx, NullClient(), model_name="none")
    assert verdict.corruption_score > 0
    assert verdict.flags
    assert verdict.agent_outputs == {}  # no LLM ran
    assert verdict.explanation_bg


def test_stub_llm_tailored_spec_triggers_hard_trip():
    rec = make_record(natural_key="STUB", bids_count=1, full_text="Изисква се точно определен надпис на точно определено място.")
    view, ctx = _view_and_ctx(rec)
    stub = StubClient(
        {
            SpecRiggingOutput: SpecRiggingOutput(
                rigging_confidence=0.9,
                suspicious_conditions=[SuspiciousCondition(quote="точно определен надпис", why_bg="ограничава", restrictiveness=0.9)],
                rationale_bg="Скроено по мярка.",
            )
        }
    )
    verdict = analyze_view(view, ctx, stub, model_name="stub")
    assert verdict.hard_tripped is True
    assert verdict.corruption_score >= 99
    assert "spec_rigging" in verdict.agent_outputs


def test_verdict_serializes_to_ndjson():
    rec = make_record(natural_key="SER", bids_count=1)
    view, ctx = _view_and_ctx(rec)
    verdict = analyze_view(view, ctx, NullClient())
    line = verdict.to_ndjson_line()
    assert '"natural_key":"SER"' in line
    assert "corruption_score" in line
