"""Concession abuse agent: National Concession Register (НКР)."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import ConcessionAbuseOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "concession_abuse"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> ConcessionAbuseOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, ConcessionAbuseOutput)


def signals(output: ConcessionAbuseOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.abuse_confidence >= 0.3:
        out.append(
            Signal(
                key="concession_abuse_llm",
                family="concessions",
                code="GOV04",
                risk=output.abuse_confidence,
                value={
                    "operator_lock_in": output.operator_lock_in,
                    "amendment_pattern": output.amendment_pattern,
                    "limited_competition": output.limited_competition,
                },
                source_field="концесия (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за злоупотреба при концесионна процедура.",
            )
        )
    return out
