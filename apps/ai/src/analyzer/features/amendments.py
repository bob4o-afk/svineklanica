"""Amendment / execution red flags (R064-R069, R073).

Post-award amendments that inflate price/scope, or a documented gap between work
completed and the contract spec.
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal, text_blob

FAMILY = "amendments"

_AMEND_HINTS = ("анекс", "допълнително споразумение", "изменение на договор", "amendment", "change order")
_DISCREPANCY_HINTS = ("неизпълн", "некачествен", "разминаване", "не отговаря", "забавено изпълнение")


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    blob = text_blob(view)

    amendments = view.payload.get("amendments")
    n_amend = len(amendments) if isinstance(amendments, list) else 0

    if n_amend >= 1 or any(h in blob for h in _AMEND_HINTS):
        risk = clamp01(0.3 + 0.15 * max(n_amend, 1))
        out.append(
            signal(
                "contract_amended",
                FAMILY,
                risk,
                code="R064",
                value=n_amend or True,
                source_field="amendments/text",
                rationale_bg="Договорът е изменян с анекс(и) след сключване.",
            )
        )

    # R073 - discrepancy between work completed and the spec.
    if any(h in blob for h in _DISCREPANCY_HINTS):
        out.append(
            signal(
                "delivery_discrepancy",
                FAMILY,
                0.45,
                code="R073",
                value=True,
                source_field="text",
                rationale_bg="Данни за разминаване между извършеното и спецификацията.",
            )
        )

    return out
