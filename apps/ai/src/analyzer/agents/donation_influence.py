"""Donation influence agent: MVR donations register (pay-to-play)."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import DonationInfluenceOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "donation_influence"


def _donation_block(view: TenderView) -> str:
    payload = view.payload
    value = payload.get("value")
    amount = None
    currency = None
    if isinstance(value, dict):
        amount = value.get("amount")
        currency = value.get("currency")
    lines = [
        "--- Дарение за МВР ---",
        f"Дарител: {payload.get('donor') or '(няма)'}",
        f"Предмет: {payload.get('subject') or '(няма)'}",
        f"Стойност: {amount} {currency or ''}".strip(),
        f"Дата: {payload.get('donated_at') or '(няма)'}",
    ]
    raw = payload.get("raw_row")
    if isinstance(raw, dict) and raw:
        lines.append(f"Суров ред: {raw}")
    return "\n".join(lines)


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> DonationInfluenceOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{_donation_block(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, DonationInfluenceOutput)


def signals(output: DonationInfluenceOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.influence_suspicion >= 0.3:
        out.append(
            Signal(
                key="donation_influence_llm",
                family="donations",
                code="POL01",
                risk=output.influence_suspicion,
                value={
                    "donor": output.named_donor or view.payload.get("donor"),
                    "regulated_supplier": output.donor_is_regulated_or_supplier,
                    "in_kind": output.in_kind_or_vehicle,
                    "repeat": output.repeat_donor,
                },
                source_field="дарение МВР (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за необосновано влияние чрез дарение.",
            )
        )
    if output.quid_pro_quo_suspicion >= 0.5:
        out.append(
            Signal(
                key="quid_pro_quo_llm",
                family="donations",
                code="POL02",
                risk=output.quid_pro_quo_suspicion,
                source_field="quid pro quo (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за връзка дарение–поръчка (pay-to-play).",
            )
        )
    return out
