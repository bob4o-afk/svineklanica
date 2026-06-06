"""INN steering agent: brand/manufacturer steering instead of generic INN."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import INNSteeringOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "inn_steering"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> INNSteeringOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, INNSteeringOutput)


def signals(output: INNSteeringOutput | None, view: TenderView) -> list[Signal]:
    if output is None or output.steering_confidence <= 0:
        return []
    quotes = "; ".join(c.quote for c in output.suspicious_conditions[:3])
    return [
        Signal(
            key="inn_steering_llm",
            family="inn_steering",
            code="DRUG04",
            risk=output.steering_confidence,
            value=output.brand_named or quotes or None,
            source_field="INN/марка (LLM)",
            rationale_bg=output.rationale_bg or "Насочване към конкретна марка вместо МНН.",
        )
    ]
