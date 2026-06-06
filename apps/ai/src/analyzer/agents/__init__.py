"""LangChain/Gemini analytic agents + the narrative aggregator.

Each analytic agent loads a Markdown prompt, sends the record to Gemini with a
Pydantic structured-output schema, and exposes ``signals()`` to turn the model's
confidences into scoreable :class:`~analyzer.schemas.Signal` objects. When no
model is available the agents return ``None`` and contribute no signals.
"""

from __future__ import annotations

from . import aggregator, collusion, entity, lifecycle, scope, spec_rigging

ANALYTIC_AGENTS = [spec_rigging, scope, lifecycle, entity, collusion]

__all__ = ["ANALYTIC_AGENTS", "aggregator", "collusion", "entity", "lifecycle", "scope", "spec_rigging"]
