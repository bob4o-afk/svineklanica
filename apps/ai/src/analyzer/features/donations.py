"""MVR donations register red flags (pay-to-play / undue influence)."""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import is_round_amount, signal

FAMILY = "donations"

# Heuristic threshold for unusually large single donation (BGN)
_LARGE_DONATION_BGN = 50000.0


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    payload = view.payload

    donor = payload.get("donor")
    if not donor or (isinstance(donor, str) and not donor.strip()):
        out.append(
            signal(
                "missing_donor",
                FAMILY,
                0.5,
                code="DON01",
                source_field="donor",
                rationale_bg="Липсва име на дарител в регистъра на дарения.",
            )
        )
        return out

    amount = view.value_amount
    if amount is None:
        value = payload.get("value")
        if isinstance(value, dict):
            raw_amount = value.get("amount")
            if isinstance(raw_amount, (int, float)):
                amount = float(raw_amount)

    if amount is None or amount <= 0:
        out.append(
            signal(
                "in_kind_donation",
                FAMILY,
                0.55,
                code="DON02",
                source_field="value.amount",
                rationale_bg="Дарение в натура (без парична стойност) — по-трудно оценимо и по-рисково.",
            )
        )
    else:
        if amount >= _LARGE_DONATION_BGN:
            out.append(
                signal(
                    "large_donation",
                    FAMILY,
                    0.6,
                    code="DON03",
                    value=amount,
                    source_field="value.amount",
                    rationale_bg=f"Единично дарение над {_LARGE_DONATION_BGN:,.0f} лв. — провери за необосновано влияние.",
                )
            )
        if is_round_amount(amount):
            out.append(
                signal(
                    "round_donation",
                    FAMILY,
                    0.45,
                    code="DON04",
                    value=amount,
                    source_field="value.amount",
                    rationale_bg="Подозрително кръгла сума на дарението.",
                )
            )

    dup_count = _donor_count_in_corpus(ctx, str(donor))
    if dup_count >= 2:
        out.append(
            signal(
                "repeat_donor",
                FAMILY,
                0.65,
                code="DON05",
                value=dup_count,
                source_field="donor",
                rationale_bg=f"Дарителят {donor} се среща в {dup_count} записа — повтарящ се дарител.",
            )
        )

    return out


def _donor_count_in_corpus(ctx: AnalysisContext, name: str) -> int:
    norm = name.strip().lower()
    count = 0
    for v in ctx.views:
        d = v.payload.get("donor")
        if isinstance(d, str) and d.strip().lower() == norm:
            count += 1
    return count
