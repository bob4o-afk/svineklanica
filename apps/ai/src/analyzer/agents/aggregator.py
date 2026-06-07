"""Aggregator: write the citizen-facing narrative (never sets the score)."""

from __future__ import annotations

from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import AggregatorOutput, Flag, Signal
from .base import load_prompt, tender_brief

NAME = "aggregator"


def _top_reasons(signals: list[Signal], limit: int = 5) -> list[str]:
    ranked = sorted((s for s in signals if s.rationale_bg), key=lambda s: s.risk, reverse=True)
    seen: list[str] = []
    for s in ranked:
        if s.rationale_bg not in seen:
            seen.append(s.rationale_bg)
        if len(seen) >= limit:
            break
    return seen


def _fallback(
    view: TenderView,
    score: float,
    level: str,
    signals: list[Signal],
    *,
    sphere: str = "",
    category: str = "",
) -> AggregatorOutput:
    # Sphere / category / level / score all render as BADGES in the UI (severity chip,
    # sector + sphere badges, the score %), so the headline stays a clean one-sentence
    # "what's suspicious" — matching the LLM path (prompts/aggregator.md). Never bake a
    # "[сфера / категория] ниво (NN/100):" prefix into the title.
    reasons = _top_reasons(signals)
    if not reasons:
        return AggregatorOutput(
            headline_bg="Няма съществени сигнали за корупция по наличните данни.",
            explanation_bg="Не са открити значими червени флагове по наличните данни.",
        )
    return AggregatorOutput(
        headline_bg=reasons[0],
        explanation_bg="Основания: " + " ".join(f"• {r}" for r in reasons),
    )


def run(
    client: StructuredLLM,
    view: TenderView,
    score: float,
    level: str,
    signals: list[Signal],
    flags: list[Flag],
    *,
    sphere: str = "",
    category: str = "",
) -> AggregatorOutput:
    """LLM narrative with a deterministic fallback (so it always returns)."""
    if not client.available:
        return _fallback(view, score, level, signals, sphere=sphere, category=category)

    system = load_prompt(NAME)
    reasons = "\n".join(f"- {r}" for r in _top_reasons(signals, limit=8)) or "- (няма)"
    flag_types = ", ".join(sorted({f.type for f in flags})) or "(няма)"
    meta = ""
    if sphere or category:
        meta = f"Сфера: {sphere or '(няма)'}\nКатегория: {category or '(няма)'}\n"
    user = (
        f"{tender_brief(view)}\n\n"
        f"{meta}"
        f"Изчислен скор: {score:.0f}/100\nНиво: {level}\nФлагове: {flag_types}\n\n"
        f"Сигнали (със източник, по важност):\n{reasons}"
    )
    result = client.analyze(system, user, AggregatorOutput)
    if result is None or not (result.headline_bg or result.explanation_bg):
        return _fallback(view, score, level, signals, sphere=sphere, category=category)
    return result
