"""LLM category router for ambiguous cross-cutting healthcare records."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import CategoryRouterOutput, Signal

NAME = "category_router"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> CategoryRouterOutput | None:
    if not client.available:
        return None
    from .base import load_prompt, tender_brief

    system = load_prompt("category_router")
    user = (
        f"{tender_brief(view)}\n\n"
        "Избери flow_key: drugs, procurement, jobs, assets."
    )
    return client.analyze(system, user, CategoryRouterOutput)


def signals(output: CategoryRouterOutput | None, view: TenderView) -> list[Signal]:
    return []
