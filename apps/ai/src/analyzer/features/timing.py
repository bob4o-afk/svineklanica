"""Timing red flags (R003, R014, R015, R030, R060, R061, R062).

Short windows exclude unwarned bidders; abnormally long award->signature gaps
can signal post-award negotiation ("kickback" window, World Bank red flag).
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal

FAMILY = "timing"


def _days(a, b) -> float | None:  # noqa: ANN001
    if a is None or b is None:
        return None
    return (b - a).total_seconds() / 86400.0


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []

    # R003 / R014 - submission window too short (< 14 days is the classic cutoff).
    window = _days(view.published_at, view.deadline)
    if window is not None and 0 <= window < 14:
        risk = clamp01((14 - window) / 14)
        out.append(
            signal(
                "short_submission_period",
                FAMILY,
                0.3 + 0.6 * risk,
                code="R014",
                value=round(window, 1),
                source_field="published_at..deadline",
                rationale_bg=f"Срокът за подаване е само {window:.0f} дни (<14) — възпира конкуренти.",
            )
        )

    # R060 - long time between award and contract signature.
    sign_gap = _days(view.award_date, view.signed_at)
    if sign_gap is not None and sign_gap > 30:
        out.append(
            signal(
                "long_award_to_signature",
                FAMILY,
                clamp01(0.3 + (sign_gap - 30) / 120),
                code="R060",
                value=round(sign_gap, 1),
                source_field="award_date..signed_at",
                rationale_bg=f"Дълъг период награждаване→подпис ({sign_gap:.0f} дни).",
            )
        )

    # R030 - a late submission ended up winning (needs bidder timestamps + deadline).
    if view.deadline and view.bidders:
        for b in view.bidders:
            if b.submitted_at and b.submitted_at > view.deadline and _is_winner(view, b):
                out.append(
                    signal(
                        "late_bid_won",
                        FAMILY,
                        0.8,
                        code="R030",
                        value=b.name,
                        source_field="bidders.submitted_at",
                        rationale_bg="Печелившата оферта е подадена след крайния срок.",
                    )
                )
                break

    return out


def _is_winner(view: TenderView, bidder) -> bool:  # noqa: ANN001
    return bool(view.winner_name) and bidder.name.strip().lower() == view.winner_name.strip().lower()
