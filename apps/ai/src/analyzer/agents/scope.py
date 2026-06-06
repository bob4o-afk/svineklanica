"""Scope-realism agent: implausible scope / overpricing narrative."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import ScopeRealismOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "scope"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> ScopeRealismOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    median, mad, n = ctx.cpv_price_stats(view)
    peer = f"\nМедиана за CPV категорията: {median:,.0f} (n={n})" if median else ""
    user = f"{tender_brief(view)}{peer}\n\n{full_text_block(view)}"
    return client.analyze(system, user, ScopeRealismOutput)


def signals(output: ScopeRealismOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.scope_implausibility > 0:
        out.append(
            Signal(
                key="implausible_scope_llm",
                family="scope",
                risk=output.scope_implausibility,
                value="; ".join(output.what_to_verify_bg[:3]) or None,
                source_field="обхват (LLM)",
                rationale_bg=output.rationale_bg or "Нереалистичен обхват спрямо предмета/стойността.",
            )
        )
    if output.overpricing_suspicion > 0:
        out.append(
            Signal(
                key="overpricing_llm",
                family="pricing",
                risk=output.overpricing_suspicion,
                source_field="цена (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за надценяване спрямо пазара.",
            )
        )
    return out
