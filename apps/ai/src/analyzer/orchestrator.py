"""The pipeline: route -> flow features -> parallel LLM agents -> scorer -> narrative."""

from __future__ import annotations

from langchain_core.runnables import RunnableLambda, RunnableParallel

from . import features
from .agents import aggregator
from .context import AnalysisContext
from .flows import get_flow
from .llm import StructuredLLM
from .payload import TenderView
from .routing import resolve_sphere, route_flow
from .schemas import Signal, VerdictRecord, utcnow
from .scoring import score_record


def _build_agent_runnable(
    client: StructuredLLM,
    ctx: AnalysisContext,
    agents: tuple,
) -> RunnableParallel:
    """RunnableParallel mapping agent name -> structured output for a view."""
    branches = {
        module.NAME: RunnableLambda(lambda v, m=module: m.run(client, v, ctx))
        for module in agents
    }
    return RunnableParallel(branches)


def _agent_signals(outputs: dict, view: TenderView, agents: tuple) -> tuple[list[Signal], dict]:
    signals: list[Signal] = []
    serialized: dict = {}
    for module in agents:
        out = outputs.get(module.NAME)
        if out is not None:
            signals.extend(module.signals(out, view))
            serialized[module.NAME] = out.model_dump()
    return signals, serialized


def _resolve_sphere(view: TenderView, flow) -> str:
    payload_sphere = view.payload.get("sphere")
    if isinstance(payload_sphere, str) and payload_sphere.strip():
        return payload_sphere.strip()
    return flow.sphere or ""


def analyze_view(
    view: TenderView,
    ctx: AnalysisContext,
    client: StructuredLLM,
    *,
    model_name: str = "",
    weights: dict[str, float] | None = None,
    agent_runnable: RunnableParallel | None = None,
    flow_key: str | None = None,
) -> VerdictRecord:
    """Run the full pipeline on one record and return its verdict."""
    sphere = resolve_sphere(view)
    resolved_flow_key = flow_key or route_flow(view, client, sphere)
    flow = get_flow(resolved_flow_key, sphere)

    deterministic = features.extract_for_flow(view, ctx, flow.feature_modules)

    runnable = agent_runnable or _build_agent_runnable(client, ctx, flow.agents)
    agent_outputs = runnable.invoke(view)
    llm_signals, serialized_outputs = _agent_signals(agent_outputs, view, flow.agents)

    all_signals = deterministic + llm_signals
    score, level, hard_tripped, scored, flags, _reason = score_record(view, all_signals, weights)

    sphere = _resolve_sphere(view, flow)
    category = flow.category

    narrative = aggregator.run(
        client, view, score, level, scored, flags, sphere=sphere, category=category
    )

    return VerdictRecord(
        source=view.source,
        natural_key=view.natural_key,
        source_url=view.source_url,
        analyzed_at=utcnow(),
        model=model_name,
        corruption_score=score,
        level=level,
        hard_tripped=hard_tripped,
        signals=scored,
        flags=flags,
        agent_outputs=serialized_outputs,
        headline_bg=narrative.headline_bg,
        explanation_bg=narrative.explanation_bg,
        sphere=sphere,
        category=category,
        flow_key=resolved_flow_key,
    )
