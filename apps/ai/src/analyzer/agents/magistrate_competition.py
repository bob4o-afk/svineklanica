"""Magistrate competition agent: rigged judicial appointments."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import MagistrateCompetitionOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "magistrate_competition"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> MagistrateCompetitionOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, MagistrateCompetitionOutput)


def signals(output: MagistrateCompetitionOutput | None, view: TenderView) -> list[Signal]:
    if output is None or output.rigging_confidence <= 0:
        return []
    quotes = "; ".join(c.quote for c in output.suspicious_conditions[:3])
    return [
        Signal(
            key="magistrate_competition_llm",
            family="jobs",
            code="JUD01",
            risk=output.rigging_confidence,
            value={
                "rushed": output.rushed_procedure,
                "atestation": output.atestation_manipulation,
                "tailored_seniority": output.tailored_seniority,
                "parachuting": output.parachuting_candidate,
                "single_candidate": output.single_eligible_candidate,
                "holiday": output.holiday_timing,
            },
            source_field="конкурс магистрати (LLM)",
            rationale_bg=output.rationale_bg or quotes or "Съмнение за нагласен конкурс за магистрат.",
        )
    ]
