"""Conflict / kinship agent for appointments and competitions."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import ConflictKinshipOutput, Signal
from .base import entity_aggregates_block, full_text_block, load_prompt, tender_brief

NAME = "conflict_kinship"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> ConflictKinshipOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = (
        f"{tender_brief(view)}\n\n"
        f"{entity_aggregates_block(view, ctx)}\n\n"
        f"{full_text_block(view)}"
    )
    return client.analyze(system, user, ConflictKinshipOutput)


def signals(output: ConflictKinshipOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.kinship_confidence >= 0.4:
        out.append(
            Signal(
                key="conflict_kinship_llm",
                family="conflict",
                code="CONF01",
                risk=output.kinship_confidence,
                value=output.named_parties or None,
                source_field="роднински връзки (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за роднински връзки при назначение.",
            )
        )
    if output.conflict_confidence >= 0.4:
        out.append(
            Signal(
                key="conflict_of_interest_llm",
                family="conflict",
                code="CONF02",
                risk=output.conflict_confidence,
                value=output.named_parties or None,
                source_field="конфликт на интереси (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за конфликт на интереси.",
            )
        )
    return out
