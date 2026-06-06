"""Pricing red flags (R016, R029, R031, R059, plus round-number heuristics).

Outlier value vs the CPV-category peers (robust z-score via median/MAD),
suspiciously round amounts, and a large award->final gap.
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, is_round_amount, signal

FAMILY = "pricing"


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    amount = view.value_amount

    # R016 - value far from the category norm (robust z-score).
    median, mad, n = ctx.cpv_price_stats(view)
    if amount and median and mad and mad > 0:
        z = abs(amount - median) / (1.4826 * mad)
        if z >= 3.5:
            out.append(
                signal(
                    "price_outlier",
                    FAMILY,
                    clamp01(0.3 + (z - 3.5) / 6),
                    code="R016",
                    value={"amount": amount, "median": median, "z": round(z, 2), "n": n},
                    source_field="value_amount vs CPV peers",
                    rationale_bg=(
                        f"Стойността се отклонява силно (z≈{z:.1f}) от медианата "
                        f"{median:,.0f} за CPV категорията."
                    ),
                )
            )

    # Round-number contract value.
    if is_round_amount(amount):
        out.append(
            signal(
                "round_amount",
                FAMILY,
                0.25,
                value=amount,
                source_field="value_amount",
                rationale_bg="Подозрително кръгла стойност на договора.",
            )
        )

    # R059 - large difference between award value and final contract amount.
    if view.value_amount and view.final_amount and view.value_amount > 0:
        growth = (view.final_amount - view.value_amount) / view.value_amount
        if growth > 0.15:
            out.append(
                signal(
                    "cost_overrun",
                    FAMILY,
                    clamp01(0.3 + growth),
                    code="R059",
                    value={"award": view.value_amount, "final": view.final_amount, "growth": round(growth, 2)},
                    source_field="value_amount..final_amount",
                    rationale_bg=f"Крайната стойност е с {growth * 100:.0f}% над договорената.",
                )
            )

    return out
