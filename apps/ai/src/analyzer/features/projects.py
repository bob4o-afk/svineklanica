"""Road infrastructure project red flags (api_projects and related)."""

from __future__ import annotations

import re
from datetime import datetime

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import signal, text_blob

FAMILY = "projects"

_STALLED_KEYWORDS = ("замразен", "спрян", "забавен", "преустановен", "отложен")
_STATUS_TAIL_RE = re.compile(r"\.\s*статус\s*:", re.IGNORECASE)
_FUNDING_TAIL_RE = re.compile(r"\.\s*финансиране\s*:", re.IGNORECASE)


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    blob = text_blob(view)
    payload = view.payload

    norm_title = _normalize_project_title(view.title or "")
    if norm_title:
        dup = _project_count_in_corpus(ctx, norm_title)
        if dup >= 2:
            out.append(
                signal(
                    "repeat_project_entity",
                    FAMILY,
                    0.35,
                    code="RD02",
                    value={"title": norm_title, "count": dup},
                    source_field="title",
                    rationale_bg=f"Нормализираното име на проекта „{norm_title}“ се среща в {dup} записа.",
                )
            )

    for kw in _STALLED_KEYWORDS:
        if kw in blob:
            out.append(
                signal(
                    "stalled_project_keywords",
                    FAMILY,
                    0.4,
                    code="RD03",
                    value=kw,
                    source_field="title/full_text",
                    rationale_bg=f"Ключова дума „{kw}“ — документирано забавяне/замразяване, нужен контекст.",
                )
            )
            break

    if "в строителство" in blob and norm_title:
        dates = _project_dates_in_corpus(ctx, norm_title)
        if len(dates) >= 2 and _span_at_least_one_year(dates):
            out.append(
                signal(
                    "perpetual_construction",
                    FAMILY,
                    0.35,
                    code="RD04",
                    value={"title": norm_title, "records": len(dates)},
                    source_field="title/published_at",
                    rationale_bg=(
                        f'„В строителство" за „{norm_title}" в {len(dates)} записа '
                        "с разлика ≥1 година — провери за реален напредък."
                    ),
                )
            )

    has_status = bool(payload.get("status"))
    has_funding = bool(payload.get("funding_source"))
    title_len = len((view.title or "").strip())
    if not has_status and not has_funding and title_len < 40:
        out.append(
            signal(
                "thin_project_payload",
                FAMILY,
                0.25,
                code="RD05",
                value=title_len,
                source_field="title",
                rationale_bg="Тънък payload без структуриран статус/финансиране — ограничена видимост.",
            )
        )

    return out


def _normalize_project_title(title: str) -> str:
    t = title.strip()
    if not t:
        return ""
    for pat in (_STATUS_TAIL_RE, _FUNDING_TAIL_RE):
        m = pat.search(t)
        if m:
            t = t[: m.start()].strip()
    return t.lower()


def _project_count_in_corpus(ctx: AnalysisContext, norm_title: str) -> int:
    count = 0
    for v in ctx.views:
        other = _normalize_project_title(v.title or "")
        if other and other == norm_title:
            count += 1
    return count


def _parse_date(raw: str | None) -> datetime | None:
    if not raw or not isinstance(raw, str):
        return None
    try:
        return datetime.fromisoformat(raw.replace("Z", "+00:00"))
    except ValueError:
        return None


def _project_dates_in_corpus(ctx: AnalysisContext, norm_title: str) -> list[datetime]:
    dates: list[datetime] = []
    for v in ctx.views:
        other = _normalize_project_title(v.title or "")
        if other != norm_title:
            continue
        blob = text_blob(v)
        if "в строителство" not in blob:
            continue
        dt = _parse_date(v.published_at) or _parse_date(v.payload.get("published_at"))
        if dt:
            dates.append(dt)
    return sorted(dates)


def _span_at_least_one_year(dates: list[datetime]) -> bool:
    if len(dates) < 2:
        return False
    delta = dates[-1] - dates[0]
    return delta.days >= 365
