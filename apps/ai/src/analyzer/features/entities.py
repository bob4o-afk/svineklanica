"""Entity / network red flags (R032, R040, R043, R046, R050, serial winner).

Serial-winner streaks, buyer dependence on one supplier, conflict-of-interest
contact overlaps, debarment, and (best-effort) kinship via shared surnames.
"""

from __future__ import annotations

import re

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal

FAMILY = "entities"

_DEBAR_HINTS = ("отстранен", "санкци", "debarred", "blacklist", "черен списък")


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []

    # Serial winner: this winner appears many times across the corpus.
    wins = ctx.winner_win_count(view)
    if wins >= 5:
        out.append(
            signal(
                "serial_winner",
                FAMILY,
                clamp01(0.4 + 0.06 * (wins - 5)),
                code="R050",
                value=wins,
                source_field="winner history",
                rationale_bg=f"Изпълнителят печели подозрително често ({wins} поръчки в корпуса).",
            )
        )

    # R040 - buyer dependence: a high share of an authority's awards go to one winner.
    dep = ctx.buyer_dependence(view)
    total = ctx.authority_record_count(view)
    if dep >= 0.5 and total >= 4:
        out.append(
            signal(
                "buyer_dependence",
                FAMILY,
                clamp01(dep),
                code="R040",
                value={"share": round(dep, 2), "authority_total": total},
                source_field="authority+winner",
                rationale_bg=f"{dep * 100:.0f}% от поръчките на възложителя отиват към един изпълнител.",
            )
        )

    # R043 - winner shares contact info with a procurement official (conflict of interest).
    if _winner_shares_official_contact(view):
        out.append(
            signal(
                "conflict_contact_overlap",
                FAMILY,
                0.9,
                code="R043",
                value=True,
                source_field="winner/official contact",
                rationale_bg="Изпълнителят споделя контакт с длъжностно лице на възложителя — конфликт на интереси.",
            )
        )

    # R046 - winner is debarred / on a sanctions list.
    blob = f"{view.full_text}".lower()
    if any(h in blob for h in _DEBAR_HINTS):
        out.append(
            signal(
                "debarred_winner",
                FAMILY,
                0.7,
                code="R046",
                value=True,
                source_field="text",
                rationale_bg="Данни, че изпълнителят е отстраняван/в санкционен списък.",
            )
        )

    # Best-effort kinship: shared surname between buyer and winner names.
    kin = _shared_surname(view.buyer_name, view.winner_name)
    if kin:
        out.append(
            signal(
                "possible_kinship",
                FAMILY,
                0.5,
                value=kin,
                source_field="buyer/winner names",
                rationale_bg=(
                    f"Споделена фамилия '{kin}' между възложител и изпълнител — "
                    "възможна роднинска връзка (изисква проверка в Търговския регистър)."
                ),
            )
        )

    return out


def _winner_shares_official_contact(view: TenderView) -> bool:
    official = view.payload.get("official") or view.payload.get("contact_person")
    winner = view.payload.get("winner")
    if not isinstance(official, dict) or not isinstance(winner, dict):
        return False
    for key in ("phone", "email", "address"):
        a, b = official.get(key), winner.get(key)
        if a and b and str(a).strip().lower() == str(b).strip().lower():
            return True
    return False


_SURNAME_RE = re.compile(r"[А-Яа-яA-Za-z]{4,}(?:ов|ова|ев|ева|ски|ска|ич)\b")


def _shared_surname(a: str, b: str) -> str | None:
    """Detect a shared Bulgarian-style surname between two names (heuristic)."""
    if not a or not b:
        return None
    sa = {m.group(0).lower() for m in _SURNAME_RE.finditer(a)}
    sb = {m.group(0).lower() for m in _SURNAME_RE.finditer(b)}
    common = sa & sb
    return next(iter(common)) if common else None
