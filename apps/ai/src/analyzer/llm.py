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
from typing import Protocol, TypeVar

from pydantic import BaseModel

logger = logging.getLogger(__name__)

TSchema = TypeVar("TSchema", bound=BaseModel)


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

        structured = self._llm.with_structured_output(schema, method="json_schema")
        messages = [SystemMessage(content=system_md), HumanMessage(content=user_text)]
        try:
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
