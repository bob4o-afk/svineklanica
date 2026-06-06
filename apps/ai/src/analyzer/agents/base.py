"""Shared helpers for agents: prompt loading + input rendering."""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path

from ..context import AnalysisContext
from ..payload import TenderView

_PROMPTS_DIR = Path(__file__).resolve().parent.parent / "prompts"

# Keep prompts rich but bounded so a single record can't blow the context window.
_MAX_TEXT_CHARS = 6000


@lru_cache(maxsize=None)
def load_prompt(name: str) -> str:
    return (_PROMPTS_DIR / f"{name}.md").read_text(encoding="utf-8")


def _money(view: TenderView) -> str:
    if view.value_amount is None:
        return "неизвестна"
    cur = view.value_currency or ""
    return f"{view.value_amount:,.2f} {cur}".strip()


def tender_brief(view: TenderView) -> str:
    lines = [
        f"Източник: {view.source} ({view.source_url})",
        f"Предмет/заглавие: {view.title or '(няма)'}",
        f"Възложител: {view.buyer_name or '(няма)'}",
        f"Изпълнител/победител: {view.winner_name or '(няма)'}",
        f"Стойност: {_money(view)}",
        f"CPV: {view.cpv or '(няма)'}",
        f"Процедура/тип: {view.procedure_type or '(няма)'}",
        f"Статус: {view.status or '(няма)'}",
        f"Брой оферти: {view.bids_count if view.bids_count is not None else '(няма)'}",
    ]
    return "\n".join(lines)


def full_text_block(view: TenderView) -> str:
    text = (view.full_text or "")[:_MAX_TEXT_CHARS]
    return f"--- Текст на поръчката ---\n{text}" if text else "--- Текст на поръчката ---\n(няма)"


def bidders_block(view: TenderView) -> str:
    if not view.bidders:
        return "Оференти: (няма данни)"
    rows = []
    for b in view.bidders:
        amt = f"{b.amount:,.2f}" if b.amount else "?"
        ts = b.submitted_at.isoformat() if b.submitted_at else "?"
        flag = " [отстранен]" if b.disqualified else ""
        rows.append(f"- {b.name or '?'} | цена={amt} | подадена={ts}{flag}")
    return "Оференти:\n" + "\n".join(rows)


def entity_aggregates_block(view: TenderView, ctx: AnalysisContext) -> str:
    return "\n".join(
        [
            f"Брой победи на изпълнителя в корпуса: {ctx.winner_win_count(view)}",
            f"Брой поръчки на възложителя: {ctx.authority_record_count(view)}",
            f"Поръчки от този възложител към този изпълнител: {ctx.pair_count(view)}",
            f"Дял (зависимост възложител→изпълнител): {ctx.buyer_dependence(view):.2f}",
        ]
    )
