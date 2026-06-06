"""LangChain -> Gemini bridge (used *directly*, no raw SDK).

We talk to Gemini through LangChain's ``langchain-google-genai`` package
(``ChatGoogleGenerativeAI``) and constrain every agent to a Pydantic schema via
``with_structured_output(method="json_schema")`` — Gemini's native structured
output, the most reliable method.

A :class:`StructuredLLM` protocol lets the agents run against a stub in tests
(zero API calls, zero tokens) and against the real model in production.
"""

from __future__ import annotations

import logging
import threading
from contextlib import nullcontext
from typing import Protocol, TypeVar

from pydantic import BaseModel

logger = logging.getLogger(__name__)

TSchema = TypeVar("TSchema", bound=BaseModel)

# --- Process-wide agent governors (configured once from AnalyzerConfig) --------
# Two independent caps, both enforced at the single point every agent's LLM call
# passes through (``GeminiClient.analyze``):
#   * AGENTS_CAP      -> _agent_semaphore: max CONCURRENT in-flight calls.
#   * AGENTS_EVAL_CAP -> _eval_budget:     max TOTAL calls for the whole run.
_governor_lock = threading.Lock()
_agent_semaphore: threading.BoundedSemaphore | None = None
_eval_budget: int | None = None  # remaining evaluations; None = unlimited
_eval_exhausted_logged = False


def configure_agent_cap(concurrency: int) -> None:
    """Bound the number of simultaneous LLM agent calls (0/None => unlimited)."""
    global _agent_semaphore
    with _governor_lock:
        _agent_semaphore = (
            threading.BoundedSemaphore(concurrency) if concurrency and concurrency > 0 else None
        )


def configure_eval_budget(max_evals: int | None) -> None:
    """Bound the total LLM evaluations for the whole run (0/None => unlimited)."""
    global _eval_budget, _eval_exhausted_logged
    with _governor_lock:
        _eval_budget = max_evals if (max_evals is not None and max_evals > 0) else None
        _eval_exhausted_logged = False


def _consume_eval() -> bool:
    """Reserve one evaluation against the budget. False once it is exhausted."""
    global _eval_budget, _eval_exhausted_logged
    with _governor_lock:
        if _eval_budget is None:
            return True
        if _eval_budget <= 0:
            if not _eval_exhausted_logged:
                logger.warning(
                    "Agent evaluation budget exhausted (AGENTS_EVAL_CAP); "
                    "remaining records degrade to deterministic-only."
                )
                _eval_exhausted_logged = True
            return False
        _eval_budget -= 1
        return True


class StructuredLLM(Protocol):
    """Returns an instance of ``schema`` for a (system, user) prompt pair.

    Returns ``None`` when no model is available or the call fails, so the
    pipeline degrades gracefully to the deterministic features.
    """

    available: bool

    def analyze(self, system_md: str, user_text: str, schema: type[TSchema]) -> TSchema | None: ...


class NullClient:
    """No model configured (e.g. missing key) -> agents fall back to neutral."""

    available = False

    def analyze(self, system_md: str, user_text: str, schema: type[TSchema]) -> TSchema | None:
        return None


class GeminiClient:
    """LangChain ``ChatGoogleGenerativeAI`` with native JSON-schema output."""

    available = True

    def __init__(
        self,
        model: str,
        api_key: str,
        *,
        thinking_level: str = "low",
        temperature: float = 0.0,
    ) -> None:
        from langchain_google_genai import ChatGoogleGenerativeAI

        # Gemini 3 controls reasoning depth via thinking_level; older models
        # ignore it. Keep the call tolerant of either.
        kwargs: dict = {
            "model": model,
            "api_key": api_key,
            "temperature": temperature,
        }
        if thinking_level:
            kwargs["thinking_level"] = thinking_level
        try:
            self._llm = ChatGoogleGenerativeAI(**kwargs)
        except TypeError:
            kwargs.pop("thinking_level", None)
            self._llm = ChatGoogleGenerativeAI(**kwargs)
        self._model = model

    def analyze(self, system_md: str, user_text: str, schema: type[TSchema]) -> TSchema | None:
        from langchain_core.messages import HumanMessage, SystemMessage

        # Total-budget gate first (cheap): once AGENTS_EVAL_CAP is spent, skip the
        # call entirely and let the pipeline fall back to deterministic features.
        if not _consume_eval():
            return None

        structured = self._llm.with_structured_output(schema, method="json_schema")
        messages = [SystemMessage(content=system_md), HumanMessage(content=user_text)]
        # Concurrency gate (AGENTS_CAP): never more than N calls in flight at once.
        guard = _agent_semaphore or nullcontext()
        try:
            with guard:
                result = structured.invoke(messages)
        except Exception:  # noqa: BLE001 - never leak a key; degrade to neutral
            logger.warning("Gemini call failed for schema %s; degrading to neutral.", schema.__name__)
            return None
        if isinstance(result, schema):
            return result
        if isinstance(result, dict):
            try:
                return schema.model_validate(result)
            except Exception:  # noqa: BLE001
                return None
        return None


def build_client(config) -> StructuredLLM:  # noqa: ANN001 - avoid import cycle
    """Build the best available client from config (Gemini if a key is set)."""
    if not getattr(config, "has_api_key", False):
        logger.info("No GOOGLE_API_KEY set; running deterministic-only (LLM agents neutral).")
        return NullClient()
    try:
        return GeminiClient(
            model=config.model,
            api_key=config.api_key,
            thinking_level=config.thinking_level,
            temperature=config.temperature,
        )
    except Exception:  # noqa: BLE001
        logger.warning("Could not initialize Gemini client; running deterministic-only.")
        return NullClient()
