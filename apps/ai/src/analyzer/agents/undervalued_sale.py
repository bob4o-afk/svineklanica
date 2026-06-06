"""Undervalued asset sale agent."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import Signal, UndervaluedSaleOutput
from .base import entity_aggregates_block, full_text_block, load_prompt, tender_brief

NAME = "undervalued_sale"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> UndervaluedSaleOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = (
        f"{tender_brief(view)}\n\n"
        f"{entity_aggregates_block(view, ctx)}\n\n"
        f"{full_text_block(view)}"
    )
    return client.analyze(system, user, UndervaluedSaleOutput)


def signals(output: UndervaluedSaleOutput | None, view: TenderView) -> list[Signal]:
    if output is None or output.undervaluation_confidence <= 0:
        return []
    return [
        Signal(
            key="undervalued_sale_llm",
            family="assets",
            code="ASSET04",
            risk=output.undervaluation_confidence,
            value={
                "restrictive": output.restrictive_terms,
                "short_notice": output.short_notice,
                "insider": output.insider_buyer_pattern,
            },
            source_field="продажба активи (LLM)",
            rationale_bg=output.rationale_bg or "Съмнение за занижена продажба на държавен актив.",
        )
    ]
