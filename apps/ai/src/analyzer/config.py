"""Runtime configuration for the analyzer, loaded from the environment.

Reuses the scraper's :class:`scraper.config.Config` for the shared ingest paths
(so the analyzer reads exactly what the scraper wrote) and adds the AI-specific
knobs: the Gemini model/credentials and the pipeline limits.

Secrets (``GOOGLE_API_KEY``) live ONLY in ``apps/ai/.env`` (gitignored). This
module loads that file if present; it never logs or echoes the key.
"""

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv
from scraper.config import Config as ScraperConfig
from scraper.config import load_config as load_scraper_config

DEFAULT_MODEL = "gemini-3.1-flash-lite"
DEFAULT_THINKING_LEVEL = "low"
DEFAULT_TEMPERATURE = 0.0

# apps/ai/src/analyzer/config.py -> parents[2] == apps/ai
_PACKAGE_ROOT = Path(__file__).resolve().parents[2]


def _load_env() -> None:
    env_path = _PACKAGE_ROOT / ".env"
    if env_path.exists():
        load_dotenv(env_path, override=False)


def _env_float(name: str, default: float) -> float:
    raw = os.environ.get(name)
    if raw is None or not raw.strip():
        return default
    try:
        return float(raw)
    except ValueError:
        return default


def _env_int_or_none(name: str) -> int | None:
    raw = os.environ.get(name)
    if raw is None or not raw.strip():
        return None
    try:
        return int(raw)
    except ValueError:
        return None


@dataclass(frozen=True)
class AnalyzerConfig:
    """Everything the analyzer needs at runtime."""

    scraper: ScraperConfig
    model: str
    api_key: str | None
    thinking_level: str
    temperature: float
    batch_limit: int | None
    weights_path: Path | None

    @property
    def verdicts_dir(self) -> Path:
        return self.scraper.ingest_out_dir / "verdicts"

    @property
    def verdict_samples_dir(self) -> Path:
        return self.scraper.samples_dir / "verdicts"

    @property
    def has_api_key(self) -> bool:
        return bool(self.api_key)


def load_config() -> AnalyzerConfig:
    """Build an :class:`AnalyzerConfig` from the environment (and ``.env``)."""
    _load_env()
    scraper = load_scraper_config()

    weights_raw = os.environ.get("ANALYZER_WEIGHTS_PATH")
    weights_path = Path(weights_raw) if weights_raw and weights_raw.strip() else None

    return AnalyzerConfig(
        scraper=scraper,
        model=os.environ.get("GEMINI_MODEL", DEFAULT_MODEL),
        api_key=os.environ.get("GOOGLE_API_KEY") or None,
        thinking_level=os.environ.get("GEMINI_THINKING_LEVEL", DEFAULT_THINKING_LEVEL),
        temperature=_env_float("GEMINI_TEMPERATURE", DEFAULT_TEMPERATURE),
        batch_limit=_env_int_or_none("ANALYZE_LIMIT"),
        weights_path=weights_path,
    )
