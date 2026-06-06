"""Base class every source module extends.

A source does two things, kept separate so the parser is unit-testable offline:
- :meth:`fetch` — hit the (allow-listed) upstream and return raw payloads.
- :meth:`parse` — turn a raw payload into :class:`IngestRecord` objects. Pure;
  takes bytes, no network. Tests call this against committed fixtures.

:meth:`records` glues them: fetch, snapshot raw for provenance, parse, yield.
Skipped rows are recorded with a reason for honest logging (§7).
"""

from __future__ import annotations

import logging
from abc import ABC, abstractmethod
from collections.abc import Iterator
from dataclasses import dataclass, field
from datetime import datetime, timezone

from ..config import Config, SourceConfig
from ..contract import IngestRecord
from ..http import PoliteClient
from ..sinks import NdjsonSink
from ..spheres import CATEGORY_PROCUREMENT, classify_sphere

log = logging.getLogger("scraper.source")


def _utcnow() -> datetime:
    return datetime.now(timezone.utc)


@dataclass
class RawPayload:
    """A fetched payload plus the URL a human can open to verify it."""

    source_url: str
    content: bytes
    ext: str = "bin"
    fetched_at: datetime = field(default_factory=_utcnow)
    meta: dict = field(default_factory=dict)


class Source(ABC):
    """Abstract base for a single procurement data source."""

    #: Stable source id, also the NDJSON filename stem and env prefix.
    id: str = ""
    #: File extension for raw snapshots (csv/xml/json/html...).
    raw_ext: str = "bin"
    #: Optional fixed sphere for this source.
    sphere: str | None = None
    #: Default category for this source.
    category: str = CATEGORY_PROCUREMENT

    def __init__(self, client: PoliteClient, sink: NdjsonSink, source_cfg: SourceConfig,
                 config: Config) -> None:
        self.client = client
        self.sink = sink
        self.cfg = source_cfg
        self.config = config
        self.skipped: list[tuple[str, str]] = []

    @property
    def base_url(self) -> str:
        return self.cfg.base_url.rstrip("/")

    def skip(self, key: str, reason: str) -> None:
        """Record a dropped row with a reason (never silently drop)."""
        self.skipped.append((key, reason))
        log.debug("[%s] skipped %s: %s", self.id, key, reason)

    @abstractmethod
    def fetch(self) -> Iterator[RawPayload]:
        """Yield raw payloads from the upstream (network)."""
        raise NotImplementedError

    @abstractmethod
    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        """Turn one raw payload into normalized records (pure, no network)."""
        raise NotImplementedError

    def records(self, limit: int | None = None) -> Iterator[IngestRecord]:
        """Fetch -> snapshot raw -> parse -> yield, honoring ``limit``."""
        count = 0
        for payload in self.fetch():
            self.sink.save_raw(payload.content, ext=payload.ext or self.raw_ext)
            for record in self.parse(payload):
                # Tag with category and sphere — never override what a source
                # (the canonical builder) already set (contract.py v2).
                record.payload.setdefault("category", self.category)

                if self.sphere:
                    record.payload.setdefault("sphere", self.sphere)
                elif "sphere" not in record.payload:
                    # Infer from authority name or CPV. In v2 the CPV lives under
                    # the typed `tender` block; tolerate a legacy top-level `cpv`.
                    authority = record.payload.get("authority", {})
                    auth_name = authority.get("name") if isinstance(authority, dict) else str(authority)
                    tender = record.payload.get("tender", {})
                    cpv = (tender.get("cpv_code") if isinstance(tender, dict) else None) \
                        or record.payload.get("cpv")
                    inferred = classify_sphere(auth_name, cpv)
                    if inferred:
                        record.payload["sphere"] = inferred

                yield record
                count += 1
                if limit is not None and count >= limit:
                    return
