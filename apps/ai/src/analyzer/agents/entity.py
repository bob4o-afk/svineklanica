"""Entity-network agent: serial winner, shell clusters, kinship / conflict."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import EntityNetworkOutput, Signal
from .base import entity_aggregates_block, full_text_block, load_prompt, tender_brief

NAME = "entity"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> EntityNetworkOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = (
        f"{tender_brief(view)}\n\n--- Агрегати ---\n{entity_aggregates_block(view, ctx)}\n\n"
        f"{full_text_block(view)}"
    )
    return client.analyze(system, user, EntityNetworkOutput)


def signals(output: EntityNetworkOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.serial_winner_suspicion > 0:
        out.append(
            Signal(
                key="serial_winner_llm",
                family="entities",
                risk=output.serial_winner_suspicion,
                source_field="мрежа (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за сериен победител / фаворизиране.",
            )
        )
    if output.kinship_suspicion > 0:
        out.append(
            Signal(
                key="kinship_llm",
                family="entities",
                risk=output.kinship_suspicion,
                source_field="мрежа (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за роднинска връзка (за проверка).",
            )
        )
    if output.conflict_of_interest > 0:
        out.append(
            Signal(
                key="conflict_of_interest_llm",
                family="entities",
                risk=output.conflict_of_interest,
                source_field="мрежа (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за конфликт на интереси.",
            )
        )
    return out
