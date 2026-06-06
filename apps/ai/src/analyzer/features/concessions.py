"""Concession procedure red flags."""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import is_round_amount, signal

FAMILY = "concessions"


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []

    if view.bids_count is not None and view.bids_count <= 1:
        out.append(
            signal(
                "single_bidder",
                FAMILY,
                0.45,
                code="CON01",
                value=view.bids_count,
                source_field="bids_count",
                rationale_bg=(
                    "Само един кандидат при концесия. Често срещано и слаб самостоятелен сигнал — "
                    "тежи в комбинация с други белези."
                ),
            )
        )

    amount = view.final_amount or view.value_amount or view.estimated_amount
    if is_round_amount(amount):
        out.append(
            signal(
                "round_amount",
                FAMILY,
                0.15,
                code="CON02",
                value=amount,
                source_field="contract_value",
                rationale_bg="Кръгла сума при концесия — слаб индикатор, нужен контекст.",
            )
        )

    return out
