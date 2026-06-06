"""High-level official property declaration red flags."""

from __future__ import annotations

from datetime import datetime, timezone

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import signal

FAMILY = "gov_wealth"

_DECLARATION_DEADLINE_MONTH = 5
_DECLARATION_DEADLINE_DAY = 15


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    payload = view.payload

    official = payload.get("official_name")
    if not official:
        out.append(
            signal(
                "missing_official",
                FAMILY,
                0.3,
                code="GOVD01",
                source_field="official_name",
                rationale_bg="Липсва име на длъжностно лице в записа (вероятно непълно извличане).",
            )
        )
        return out

    declared_at = view.published_at
    if declared_at is None:
        from scraper.normalize import parse_date

        declared_at = parse_date(payload.get("declared_at"), dayfirst=False)

    if declared_at is None:
        out.append(
            signal(
                "missing_declaration_date",
                FAMILY,
                0.3,
                code="GOVD02",
                source_field="declared_at",
                rationale_bg="Липсва дата на имуществена декларация (възможен пропуск при описа).",
            )
        )
    elif _is_late_declaration(declared_at):
        out.append(
            signal(
                "late_declaration",
                FAMILY,
                0.3,
                code="GOVD03",
                value=declared_at.isoformat(),
                source_field="declared_at",
                rationale_bg=(
                    "Декларацията е подадена след крайния срок (15 май). "
                    "Закъснението е често и обикновено маловажно нарушение — слаб самостоятелен сигнал."
                ),
            )
        )

    institution = payload.get("institution") or payload.get("position")
    if not institution:
        out.append(
            signal(
                "incomplete_declaration_row",
                FAMILY,
                0.25,
                code="GOVD04",
                source_field="institution/position",
                rationale_bg="Непълен запис — липсва институция/длъжност в декларацията.",
            )
        )

    dup_count = _official_count_in_corpus(ctx, official)
    if dup_count >= 2:
        out.append(
            signal(
                "duplicate_official_filings",
                FAMILY,
                0.3,
                code="GOVD05",
                value=dup_count,
                source_field="official_name",
                rationale_bg=f"Лицето {official} се среща в {dup_count} записа — провери за дубли/несъответствия.",
            )
        )

    return out


def _is_late_declaration(dt: datetime) -> bool:
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    deadline = datetime(dt.year, _DECLARATION_DEADLINE_MONTH, _DECLARATION_DEADLINE_DAY, tzinfo=timezone.utc)
    return dt > deadline


def _official_count_in_corpus(ctx: AnalysisContext, name: str) -> int:
    norm = name.strip().lower()
    count = 0
    for v in ctx.views:
        o = v.payload.get("official_name")
        if isinstance(o, str) and o.strip().lower() == norm:
            count += 1
    return count
