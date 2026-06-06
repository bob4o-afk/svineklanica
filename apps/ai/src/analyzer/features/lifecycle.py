"""Lifecycle / document red flags (cancel-after-award, re-tender, doc-clone).

Captures the user's "announced-then-cancelled, re-issued with tweaked specs"
pattern, plus near-duplicate tenders (template reuse / doc clone) detected via
the embedding index when available.
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal, text_blob

FAMILY = "lifecycle"

_CANCEL_HINTS = (
    "прекратен",
    "прекратяване",
    "отменен",
    "оттеглен",
    "cancelled",
    "terminated",
    "withdrawn",
)


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    blob = text_blob(view)

    # Cancelled / terminated procedure.
    cancelled = any(h in blob for h in _CANCEL_HINTS) or "cancel" in (view.status or "").lower()
    if cancelled:
        # Higher risk if it was cancelled *after* bids/award (intended winner lost).
        post_award = bool(view.award_date or view.winner_name or "след" in blob)
        out.append(
            signal(
                "cancelled_procedure",
                FAMILY,
                0.7 if post_award else 0.4,
                value=view.status or "cancelled",
                source_field="status/text",
                rationale_bg=(
                    "Процедурата е прекратена"
                    + (" след отваряне/възлагане — възможно е 'грешният' да е печелил." if post_award else ".")
                ),
            )
        )

    # Re-tender / doc-clone via embedding similarity to another record.
    if view.corpus_index is not None:
        for idx, score in ctx.most_similar(view.corpus_index, top_k=3):
            if score < 0.95:
                break
            other = ctx.views[idx]
            same_authority = _same(view.buyer_name, other.buyer_name)
            if same_authority and cancelled:
                out.append(
                    signal(
                        "reissue_after_cancel",
                        FAMILY,
                        0.75,
                        value={"similar": other.natural_key, "score": round(score, 3)},
                        source_field="embedding similarity",
                        rationale_bg="Почти идентична поръчка от същия възложител — възможно повторно пускане.",
                    )
                )
            elif score >= 0.985 and not same_authority:
                out.append(
                    signal(
                        "doc_clone",
                        FAMILY,
                        clamp01(0.3 + (score - 0.985) * 20),
                        value={"similar": other.natural_key, "score": round(score, 3)},
                        source_field="embedding similarity",
                        rationale_bg="Документацията почти дублира друга поръчка (шаблон/копи-пейст).",
                    )
                )
            break  # only consider the single closest match

    return out


def _same(a: str, b: str) -> bool:
    return bool(a) and bool(b) and a.strip().lower() == b.strip().lower()
