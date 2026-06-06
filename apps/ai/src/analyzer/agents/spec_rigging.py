"""Spec-rigging agent: tailor-made / restrictive technical requirements."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import SpecRiggingOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "spec_rigging"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> SpecRiggingOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, SpecRiggingOutput)


def signals(output: SpecRiggingOutput | None, view: TenderView) -> list[Signal]:
    if output is None or output.rigging_confidence <= 0:
        return []
    quotes = "; ".join(c.quote for c in output.suspicious_conditions[:3])
    rationale = output.rationale_bg or "Условия, скроени по мярка, ограничават конкуренцията."
    return [
        Signal(
            key="tailored_spec_llm",
            family="specs",
            code="R007",
            risk=output.rigging_confidence,
            value=quotes or None,
            source_field="спецификации (LLM)",
            rationale_bg=rationale,
        )
    ]
