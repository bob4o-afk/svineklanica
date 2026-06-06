"""Deterministic red-flag feature extractors (the math, no LLM).

Each module exposes ``extract(view, ctx) -> list[Signal]``. :func:`extract_all`
runs them all. Catalog codes (``R0xx``) trace each signal to the Open Contracting
"Red flags in public procurement" reference; families group correlated signals so
the scorer does not double-count.
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from . import amendments, collusion, competition, entities, lifecycle, pricing, thresholds, timing

_MODULES = [
    competition,
    timing,
    thresholds,
    pricing,
    amendments,
    collusion,
    entities,
    lifecycle,
]


def extract_all(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    return extract_for_flow(view, ctx, _MODULES)


def extract_for_flow(view: TenderView, ctx: AnalysisContext, modules) -> list[Signal]:
    signals: list[Signal] = []
    for module in modules:
        try:
            signals.extend(module.extract(view, ctx))
        except Exception:  # noqa: BLE001 - one bad extractor must not kill the run
            continue
    return signals


__all__ = ["extract_all", "extract_for_flow"]
