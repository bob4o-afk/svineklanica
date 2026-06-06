"""Collusion agent: bid-rigging / cartel patterns across bidders."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import CollusionOutput, Signal
from .base import bidders_block, load_prompt, tender_brief

NAME = "collusion"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> CollusionOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{bidders_block(view)}"
    return client.analyze(system, user, CollusionOutput)


def signals(output: CollusionOutput | None, view: TenderView) -> list[Signal]:
    if output is None or output.collusion_confidence <= 0:
        return []
    return [
        Signal(
            key="collusion_llm",
            family="collusion",
            risk=output.collusion_confidence,
            value=output.pattern_type,
            source_field="оферти (LLM)",
            rationale_bg=output.rationale_bg or f"Съмнение за тръжна манипулация ({output.pattern_type}).",
        )
    ]
