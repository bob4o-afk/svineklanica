"""Rigged job competition agent: short deadline, tailor-made eligibility."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import RiggedCompetitionOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "rigged_competition"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> RiggedCompetitionOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, RiggedCompetitionOutput)


def signals(output: RiggedCompetitionOutput | None, view: TenderView) -> list[Signal]:
    if output is None or output.rigging_confidence <= 0:
        return []
    quotes = "; ".join(c.quote for c in output.suspicious_conditions[:3])
    return [
        Signal(
            key="rigged_competition_llm",
            family="jobs",
            code="JOB03",
            risk=output.rigging_confidence,
            value={
                "short_deadline": output.short_deadline,
                "hyper_specific": output.hyper_specific_eligibility,
                "single_candidate": output.single_eligible_candidate,
                "holiday": output.holiday_timing,
            },
            source_field="конкурс (LLM)",
            rationale_bg=output.rationale_bg or quotes or "Съмнение за нагласен конкурс за работа.",
        )
    ]
