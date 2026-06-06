"""Competition / procedure red flags (R004, R005, R010, R013, R018-R020, R063)."""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import looks_non_open, signal, text_blob

FAMILY = "competition"


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []

    # R018 - single bid received.
    if view.bids_count is not None:
        if view.bids_count <= 1:
            out.append(
                signal(
                    "single_bidder",
                    FAMILY,
                    0.85,
                    code="R018",
                    value=view.bids_count,
                    source_field="bids_count",
                    rationale_bg="Получена е само една оферта — липсва конкуренция.",
                )
            )
        elif view.bids_count == 2:
            # R019 - low number of bidders for category.
            out.append(
                signal(
                    "few_bidders",
                    FAMILY,
                    0.4,
                    code="R019",
                    value=view.bids_count,
                    source_field="bids_count",
                    rationale_bg="Много малък брой оференти (2) — слаба конкуренция.",
                )
            )

    # R010 / R013 - unjustified non-competitive / non-open procedure.
    hint = looks_non_open(view)
    if hint:
        out.append(
            signal(
                "non_open_procedure",
                FAMILY,
                0.55,
                code="R010",
                value=hint,
                source_field="procedure_type",
                rationale_bg=f"Непублична/пряка процедура ('{hint}') ограничава конкуренцията.",
            )
        )

    # R020 - tender has a complaint (a КЗК appeal is a strong contextual signal).
    blob = text_blob(view)
    if "кзк" in blob or "жалба" in blob or "обжалван" in blob:
        out.append(
            signal(
                "complaint",
                FAMILY,
                0.45,
                code="R020",
                value=True,
                source_field="text",
                rationale_bg="Процедурата е била обжалвана/има жалба (напр. пред КЗК).",
            )
        )

    return out
