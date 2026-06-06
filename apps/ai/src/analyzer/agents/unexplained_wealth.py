"""Unexplained wealth agent: magistrate property declarations (ZSV art. 19a)."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import Signal, UnexplainedWealthOutput
from .base import full_text_block, load_prompt, tender_brief

NAME = "unexplained_wealth"


def _declaration_block(view: TenderView) -> str:
    payload = view.payload
    lines = [
        "--- Имуществена декларация ---",
        f"Магистрат: {payload.get('magistrate') or '(няма)'}",
        f"Длъжност: {payload.get('position') or '(няма)'}",
        f"Съд/прокуратура: {payload.get('court') or '(няма)'}",
        f"Дата на декларация: {payload.get('declared_at') or '(няма)'}",
    ]
    raw = payload.get("raw_row")
    if isinstance(raw, dict) and raw:
        lines.append(f"Суров ред: {raw}")
    return "\n".join(lines)


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> UnexplainedWealthOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{_declaration_block(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, UnexplainedWealthOutput)


def signals(output: UnexplainedWealthOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.wealth_vs_income_suspicion >= 0.3:
        out.append(
            Signal(
                key="unexplained_wealth_llm",
                family="wealth",
                code="JUD02",
                risk=output.wealth_vs_income_suspicion,
                value=output.named_assets or None,
                source_field="имотна декларация (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за необяснимо имущество спрямо доход.",
            )
        )
    if output.undeclared_interests and output.wealth_vs_income_suspicion < 0.3:
        out.append(
            Signal(
                key="unexplained_wealth_llm",
                family="wealth",
                code="JUD03",
                risk=0.55,
                source_field="не деклариран интерес (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за недеклариран частен интерес.",
            )
        )
    return out
