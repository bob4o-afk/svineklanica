"""The scraper -> Laravel seam contract.

This is the ONE piece of logic committed before the event, because it *is* the
boundary definition, not business logic. The Python scraper emits `IngestRecord`
lines (NDJSON); `php artisan ingest:run --source=<x>` reads them and upserts on
`natural_key`. Keep this in lockstep with the Laravel ingest mapping and SOURCES.md
(see /.claude/rules/scraping.md §2). Bump `schema_version` on a breaking change.
"""

from __future__ import annotations

from datetime import datetime

from pydantic import BaseModel, ConfigDict, Field

SCHEMA_VERSION = 1


class IngestRecord(BaseModel):
    """One normalized record written to ./storage/ingest/normalized/<source>.ndjson."""

    model_config = ConfigDict(extra="forbid")

    source: str = Field(..., description="Source id, e.g. 'ted', 'egov', 'aop'.")
    natural_key: str = Field(..., description="Stable per-source key for idempotent upsert (TED notice id, registry no, EIK).")
    source_url: str = Field(..., description="A URL a human can open to verify this record.")
    fetched_at: datetime = Field(..., description="When we fetched it (ISO-8601, UTC).")
    schema_version: int = Field(default=SCHEMA_VERSION)
    payload: dict = Field(..., description="Normalized, source-specific fields. Bulgarian text stays Bulgarian (UTF-8).")

    def to_ndjson_line(self) -> str:
        """Serialize to a single UTF-8 NDJSON line (Cyrillic preserved)."""
        return self.model_dump_json()
