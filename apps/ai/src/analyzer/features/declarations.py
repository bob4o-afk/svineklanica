"""Magistrate declaration filing red flags."""

from __future__ import annotations

from datetime import datetime, timezone

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import signal

FAMILY = "wealth"

# Annual declaration deadline heuristic (Jan 31 following year)
_DECLARATION_DEADLINE_MONTH = 1
_DECLARATION_DEADLINE_DAY = 31


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    payload = view.payload

    magistrate = payload.get("magistrate")
    if not magistrate:
        out.append(
            signal(
                "missing_declaration",
                FAMILY,
                0.5,
                code="DEC01",
                source_field="magistrate",
                rationale_bg="Липсва име на магистрат в декларацията.",
            )
        )
        return out

    declared_at = view.published_at
    if declared_at is None:
        from scraper.normalize import parse_date

        # Scraper emits ISO dates (year-first); dayfirst would misread 2026-03-01 as Jan 3.
        declared_at = parse_date(payload.get("declared_at"), dayfirst=False)

    if declared_at is None:
        out.append(
            signal(
                "missing_declaration_date",
                FAMILY,
                0.55,
                code="DEC02",
                source_field="declared_at",
                rationale_bg="Липсва дата на имуществена декларация.",
            )
        )
    elif _is_late_declaration(declared_at):
        out.append(
            signal(
                "late_declaration",
                FAMILY,
                0.65,
                code="DEC03",
                value=declared_at.isoformat(),
                source_field="declared_at",
                rationale_bg="Декларацията е подадена след типичния краен срок (31 януари).",
            )
        )

    court = payload.get("court") or payload.get("position")
    if not court:
        out.append(
            signal(
                "incomplete_declaration_row",
                FAMILY,
                0.4,
                code="DEC04",
                source_field="court/position",
                rationale_bg="Непълен запис — липсва съд/длъжност в декларацията.",
            )
        )

    dup_count = _magistrate_count_in_corpus(ctx, magistrate)
    if dup_count >= 2:
        out.append(
            signal(
                "duplicate_magistrate_filings",
                FAMILY,
                0.45,
                code="DEC05",
                value=dup_count,
                source_field="magistrate",
                rationale_bg=f"Магистратът {magistrate} се среща в {dup_count} записа — провери за дубли/несъответствия.",
            )
        )

    return out


def _is_late_declaration(dt: datetime) -> bool:
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    # Late if filed after Jan 31 of the same calendar year (for prior-year declaration)
    deadline = datetime(dt.year, _DECLARATION_DEADLINE_MONTH, _DECLARATION_DEADLINE_DAY, tzinfo=timezone.utc)
    return dt > deadline


def _magistrate_count_in_corpus(ctx: AnalysisContext, name: str) -> int:
    norm = name.strip().lower()
    count = 0
    for v in ctx.views:
        m = v.payload.get("magistrate")
        if isinstance(m, str) and m.strip().lower() == norm:
            count += 1
    return count
