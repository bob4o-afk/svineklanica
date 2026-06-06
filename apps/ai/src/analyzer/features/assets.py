"""Asset disposal / auction red flags."""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, is_round_amount, signal

FAMILY = "assets"


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []

    if view.bids_count == 1 or (view.bidders and len(view.bidders) == 1):
        out.append(
            signal(
                "asset_single_bidder",
                FAMILY,
                0.4,
                code="ASSET01",
                value=1,
                source_field="bids_count",
                rationale_bg="Един участник в търг за актив — често срещано, слаб самостоятелен сигнал.",
            )
        )

    if is_round_amount(view.value_amount):
        out.append(
            signal(
                "asset_round_amount",
                FAMILY,
                0.25,
                code="ASSET02",
                value=view.value_amount,
                source_field="value",
                rationale_bg="Кръгла стойност на продажба — често срещано, проверете спрямо пазарната цена.",
            )
        )

    window = _notice_window_days(view)
    if window is not None and 0 <= window < 14:
        out.append(
            signal(
                "short_asset_notice",
                FAMILY,
                clamp01(0.3 + (14 - window) / 14 * 0.4),
                code="ASSET03",
                value=round(window, 1),
                source_field="published_at..deadline",
                rationale_bg=f"Кратък срок за търг/продажба ({window:.0f} дни).",
            )
        )

    return out


def _notice_window_days(view: TenderView) -> float | None:
    if view.published_at is None or view.deadline is None:
        return None
    return (view.deadline - view.published_at).total_seconds() / 86400.0
