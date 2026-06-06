"""Government official wealth agent: КПКОНПИ high-level declarations."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import GovOfficialWealthOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "gov_official_wealth"


def _declaration_block(view: TenderView) -> str:
    payload = view.payload
    lines = [
        "--- Имуществена декларация (високо длъжностно лице) ---",
        f"Лице: {payload.get('official_name') or '(няма)'}",
        f"Длъжност: {payload.get('position') or '(няма)'}",
        f"Институция: {payload.get('institution') or '(няма)'}",
        f"Дата на декларация: {payload.get('declared_at') or '(няма)'}",
    ]
    raw = payload.get("raw_row")
    if isinstance(raw, dict) and raw:
        lines.append(f"Суров ред: {raw}")
    return "\n".join(lines)


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> GovOfficialWealthOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{_declaration_block(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, GovOfficialWealthOutput)


def signals(output: GovOfficialWealthOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.wealth_vs_income_suspicion >= 0.3:
        out.append(
            Signal(
                key="gov_official_wealth_llm",
                family="gov_wealth",
                code="GOV02",
                risk=output.wealth_vs_income_suspicion,
                value=output.named_assets or None,
                source_field="имотна декларация ВДЛ (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за необяснимо имущество спрямо доход.",
            )
        )
    if output.undeclared_interests and output.wealth_vs_income_suspicion < 0.3:
        out.append(
            Signal(
                key="gov_official_wealth_llm",
                family="gov_wealth",
                code="GOV03",
                risk=0.55,
                source_field="не деклариран интерес (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за недеклариран частен интерес.",
            )
        )
    return out
