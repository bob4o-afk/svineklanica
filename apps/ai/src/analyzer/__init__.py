"""AI corruption-risk analyzer for the LiberHack procurement watchdog.

Reads the scraper's normalized NDJSON corpus, extracts a large catalog of
red-flag features (deterministic math + LangChain/Gemini agents over Bulgarian
text), fuses them into an auditable 0-100 corruption score with a level, and
writes a verdict sidecar NDJSON the Laravel backend ingests.
"""

from __future__ import annotations

__all__ = ["__version__"]

__version__ = "0.0.0"
