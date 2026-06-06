"""Drug overpricing agent: NCPR ceiling, repackaging markup, reimbursement gaming."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import DrugOverpricingOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "drug_overpricing"


def _drug_context_block(view: TenderView, ctx: AnalysisContext) -> str:
    lines = ["--- NCPR референции (от корпуса) ---"]
    if not ctx.drug_index:
        return lines[0] + "\n(няма зареден NCPR индекс)"
    candidates = []
    payload = view.payload
    for key in ("inn", "product"):
        val = payload.get(key)
        if isinstance(val, str):
            candidates.append(val.lower().strip())
    if view.title:
        candidates.append(view.title.lower()[:80])
    shown = 0
    for cand in candidates:
        ref = ctx.drug_index.get(cand)
        if ref:
            lines.append(
                f"- {ref.product or ref.inn}: пределна={ref.ceiling}, референтна={ref.reimbursement}, притежател={ref.holder}"
            )
            shown += 1
    if not shown:
        lines.append("(няма директно съвпадение в NCPR индекса)")
    return "\n".join(lines)


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> DrugOverpricingOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{_drug_context_block(view, ctx)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, DrugOverpricingOutput)


def signals(output: DrugOverpricingOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.overpricing_confidence > 0:
        out.append(
            Signal(
                key="drug_overpricing_llm",
                family="drug_pricing",
                code="DRUG02",
                risk=output.overpricing_confidence,
                value=output.markup_ratio,
                source_field="лекарства (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за завишена цена на лекарство.",
            )
        )
    if output.repackaging_suspicion >= 0.5:
        out.append(
            Signal(
                key="repackaging_markup",
                family="drug_pricing",
                code="DRUG03",
                risk=output.repackaging_suspicion,
                source_field="опаковка (LLM)",
                rationale_bg="Съмнение за markup чрез репакетиране/различна опаковка.",
            )
        )
    return out
