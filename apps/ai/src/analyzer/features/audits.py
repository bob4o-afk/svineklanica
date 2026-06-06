"""State Audit Office report red flags."""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import signal, text_blob

FAMILY = "audits"

_CRITICAL_KEYWORDS = ("нарушение", "несъответствие", "злоупотреба")


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    blob = text_blob(view)

    entity = _extract_entity(view)
    if entity:
        dup = _entity_count_in_corpus(ctx, entity)
        if dup >= 2:
            out.append(
                signal(
                    "repeat_audited_entity",
                    FAMILY,
                    0.35,
                    code="AUD01",
                    value={"entity": entity, "count": dup},
                    source_field="title/buyer",
                    rationale_bg=f"Институцията „{entity}“ се среща в {dup} одитни записа — провери за повтарящи се констатации.",
                )
            )

    for kw in _CRITICAL_KEYWORDS:
        if kw in blob:
            out.append(
                signal(
                    "critical_audit_keywords",
                    FAMILY,
                    0.4,
                    code="AUD02",
                    value=kw,
                    source_field="title/full_text",
                    rationale_bg=f"Ключова дума „{kw}“ в одитния запис — нужен контекст, не е самостоятелно доказателство.",
                )
            )
            break

    return out


def _extract_entity(view: TenderView) -> str | None:
    if view.buyer_name and view.buyer_name.strip():
        return view.buyer_name.strip()
    institution = view.payload.get("institution")
    if isinstance(institution, str) and institution.strip():
        return institution.strip()
    title = view.title
    if title and len(title) > 5:
        return title[:120]
    return None


def _entity_count_in_corpus(ctx: AnalysisContext, entity: str) -> int:
    norm = entity.strip().lower()
    count = 0
    for v in ctx.views:
        other = _extract_entity(v)
        if other and other.strip().lower() == norm:
            count += 1
    return count
