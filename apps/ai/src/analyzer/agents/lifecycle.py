"""Lifecycle agent: cancel-after-award, re-tender, amendment abuse."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import LifecycleOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "lifecycle"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> LifecycleOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    similar = ""
    if view.corpus_index is not None:
        for idx, score in ctx.most_similar(view.corpus_index, top_k=1):
            other = ctx.views[idx]
            similar = (
                f"\nНай-близка друга поръчка (similarity={score:.3f}): "
                f"'{other.title}' от '{other.buyer_name}' ({other.source_url})"
            )
            break
    user = f"{tender_brief(view)}{similar}\n\n{full_text_block(view)}"
    return client.analyze(system, user, LifecycleOutput)


def signals(output: LifecycleOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.cancellation_suspicion > 0:
        out.append(
            Signal(
                key="cancellation_llm",
                family="lifecycle",
                risk=output.cancellation_suspicion,
                value=output.reissue_link_bg or None,
                source_field="жизнен цикъл (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за прекратяване/повторно пускане.",
            )
        )
    if output.amendment_abuse > 0:
        out.append(
            Signal(
                key="amendment_abuse_llm",
                family="amendments",
                risk=output.amendment_abuse,
                source_field="изменения (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за злоупотреба с анекси/изменения.",
            )
        )
    return out
