"""Threshold manipulation / splitting red flags (R002, R011, R049, R055).

A contract value sitting just under a statutory threshold, or repeated sub-
threshold awards to the same authority+winner, is a classic way to dodge an open
procedure ("salami slicing").
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import BG_THRESHOLDS, clamp01, signal

FAMILY = "thresholds"


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    amount = view.value_amount

    # R002 - value sits just below a statutory threshold.
    if amount and amount > 0:
        for thr in BG_THRESHOLDS:
            if 0.90 * thr <= amount < thr:
                proximity = (amount - 0.90 * thr) / (0.10 * thr)  # 0..1, closer = higher
                out.append(
                    signal(
                        "just_under_threshold",
                        FAMILY,
                        clamp01(0.45 + 0.45 * proximity),
                        code="R002",
                        value={"amount": amount, "threshold": thr},
                        source_field="value_amount",
                        rationale_bg=(
                            f"Стойност {amount:,.0f} е точно под праг {thr:,.0f} — "
                            "възможно избягване на открита процедура."
                        ),
                    )
                )
                break

    # R011 / R055 - repeated sub-threshold awards to the same buyer+winner pair.
    pair = ctx.pair_count(view)
    if pair >= 3 and amount and amount < BG_THRESHOLDS[0]:
        out.append(
            signal(
                "repeated_sub_threshold_pair",
                FAMILY,
                clamp01(0.4 + 0.1 * (pair - 3)),
                code="R055",
                value=pair,
                source_field="authority+winner",
                rationale_bg=(
                    f"Същата двойка възложител↔изпълнител с {pair} под-прагови договора "
                    "(възможно раздробяване)."
                ),
            )
        )

    return out
