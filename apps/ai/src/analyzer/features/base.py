"""Shared helpers for the deterministic feature extractors."""

from __future__ import annotations

import math
import re

from ..payload import TenderView
from ..schemas import Signal

# Bulgarian public-procurement value thresholds (лв., ЗОП, indicative). Used to
# detect "just under a threshold" / splitting. Verify on site if exact figures
# matter; the *pattern* (value sitting just below a round threshold) is what we score.
BG_THRESHOLDS = [70000.0, 280000.0, 880000.0, 5000000.0]

_NON_OPEN_HINTS = (
    "пряко договаряне",
    "договаряне без",
    "без предварително обявление",
    "без публикуване",
    "ограничена процедура",
    "покана",
    "negotiated",
    "direct",
    "single source",
    "sole source",
)


def clamp01(x: float) -> float:
    return 0.0 if x < 0 else 1.0 if x > 1 else x


def logistic(x: float, *, k: float = 1.0, x0: float = 0.0) -> float:
    return 1.0 / (1.0 + math.exp(-k * (x - x0)))


def signal(
    key: str,
    family: str,
    risk: float,
    *,
    code: str = "",
    value=None,  # noqa: ANN001
    source_field: str = "",
    rationale_bg: str = "",
) -> Signal:
    return Signal(
        key=key,
        family=family,
        code=code,
        risk=clamp01(risk),
        value=value,
        source_field=source_field,
        rationale_bg=rationale_bg,
    )


def text_blob(view: TenderView) -> str:
    return f"{view.title}\n{view.full_text}\n{view.procedure_type}\n{view.status}".lower()


def mentions_any(text: str, needles: tuple[str, ...]) -> str | None:
    for n in needles:
        if n in text:
            return n
    return None


def looks_non_open(view: TenderView) -> str | None:
    return mentions_any(text_blob(view), _NON_OPEN_HINTS)


_ROUND_RE = re.compile(r"0{3,}$")


def is_round_amount(amount: float | None) -> bool:
    """Suspiciously round contract value (e.g. 300000, 1000000)."""
    if not amount or amount <= 0:
        return False
    return bool(_ROUND_RE.search(str(int(amount)))) and amount >= 10000
