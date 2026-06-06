"""Drug pricing vs NCPR ceiling benchmark."""

from __future__ import annotations

import re

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal

FAMILY = "drug_pricing"

_INN_RE = re.compile(r"\b([A-Z][a-z]{2,}(?:/[A-Z][a-z]+)*)\b")


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    if not ctx.drug_index:
        return out

    candidates = _drug_candidates(view)
    if not candidates:
        return out

    price = view.value_amount or view.final_amount or view.estimated_amount
    if not price or price <= 0:
        return out

    best_match = None
    best_ceiling = None
    for cand in candidates:
        ref = ctx.drug_index.get(cand)
        if ref and ref.ceiling and ref.ceiling > 0:
            best_match = ref
            best_ceiling = ref.ceiling
            break

    if not best_match or not best_ceiling:
        return out

    ratio = price / best_ceiling
    if ratio <= 1.05:
        return out

    risk = clamp01(0.5 + (ratio - 1.05) / 0.5)
    out.append(
        signal(
            "drug_above_ceiling",
            FAMILY,
            risk,
            code="DRUG01",
            value={"ratio": round(ratio, 2), "ceiling": best_ceiling, "observed": price},
            source_field="value vs NCPR ceiling",
            rationale_bg=(
                f"Наблюдаваната цена ({price:,.0f}) е {ratio:.1f}x над "
                f"пределната NCPR ({best_ceiling:,.0f}) за {best_match.product or best_match.inn}."
            ),
        )
    )
    return out


def _drug_candidates(view: TenderView) -> list[str]:
    keys: list[str] = []
    payload = view.payload
    for field in ("inn", "product", "title"):
        val = payload.get(field) if field != "title" else view.title
        if isinstance(val, str) and val.strip():
            keys.append(_normalize_key(val))

    inn = payload.get("inn")
    if isinstance(inn, str):
        keys.append(_normalize_key(inn))

    for match in _INN_RE.findall(view.title or ""):
        keys.append(_normalize_key(match))

    seen: list[str] = []
    for k in keys:
        if k and k not in seen:
            seen.append(k)
    return seen


def _normalize_key(text: str) -> str:
    return " ".join(text.lower().split())
