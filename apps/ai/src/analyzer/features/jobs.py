"""Job competition timing red flags."""

from __future__ import annotations

from datetime import datetime

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal

FAMILY = "jobs"

_HOLIDAY_MONTHS = {12, 1, 7, 8}


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []

    window = _application_window_days(view)
    if window is not None and 0 <= window < 14:
        risk = clamp01(0.4 + (14 - window) / 14 * 0.5)
        out.append(
            signal(
                "short_application_window",
                FAMILY,
                risk,
                code="JOB01",
                value=round(window, 1),
                source_field="published_at..deadline",
                rationale_bg=(
                    f"Кратък срок за кандидатстване ({window:.0f} дни) — "
                    "ограничава конкуренцията за позицията."
                ),
            )
        )

    if view.published_at and view.published_at.month in _HOLIDAY_MONTHS:
        out.append(
            signal(
                "holiday_window",
                FAMILY,
                0.45,
                code="JOB02",
                value=view.published_at.month,
                source_field="published_at",
                rationale_bg="Обявен по време на празничен/отпускарски период — по-малко кандидати.",
            )
        )

    return out


def _application_window_days(view: TenderView) -> float | None:
    start = view.published_at
    end = view.deadline
    if end is None:
        end = _parse_deadline_from_payload(view.payload)
    if start is None or end is None:
        return None
    return (end - start).total_seconds() / 86400.0


def _parse_deadline_from_payload(payload: dict) -> datetime | None:
    from scraper.normalize import parse_date

    for key in ("deadline", "application_deadline", "closing_date"):
        val = payload.get(key)
        if val:
            return parse_date(val)
    return None
