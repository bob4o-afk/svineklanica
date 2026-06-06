"""Shared HTML listing parser for sphere-specific procurement, jobs, and asset pages."""

from __future__ import annotations

import hashlib
import os
import re
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..contract import (
    Authority,
    CanonicalPayload,
    IngestRecord,
    RecordType,
    TenderDetail,
    make_record,
)
from ..encoding import decode_bytes
from ..normalize import clean_text, parse_date, to_utc_iso
from ..spheres import (
    CATEGORY_ASSETS,
    CATEGORY_AUDITS,
    CATEGORY_CONCESSIONS,
    CATEGORY_JOBS,
    CATEGORY_PROCUREMENT,
    CATEGORY_PROJECTS,
)
from .base import RawPayload, Source

_DATE_EXTRACTOR = re.compile(r"(\d{2}\.\d{2}\.\d{4})")

# A listing's category implies its canonical record_type (contract.py v2). Assets
# carry CATEGORY_ASSETS so they don't masquerade as tenders.
_CATEGORY_TO_TYPE = {
    CATEGORY_PROCUREMENT: RecordType.TENDER,
    CATEGORY_JOBS: RecordType.JOB,
    CATEGORY_AUDITS: RecordType.AUDIT,
    CATEGORY_CONCESSIONS: RecordType.CONCESSION,
    CATEGORY_PROJECTS: RecordType.PROJECT,
    CATEGORY_ASSETS: RecordType.ASSET,
}


class ListingSource(Source):
    """Parse HTML pages that list linked items (tenders, jobs, asset sales)."""

    raw_ext = "html"
    category = CATEGORY_PROCUREMENT
    pages_env: str = ""
    buyer: str = ""
    item_tags: tuple[str, ...] = ("li", "div", "p", "tr", "h3")
    min_text_len: int = 0
    extra_payload: dict | None = None
    #: Override to force a record_type; otherwise derived from `category`.
    record_type: RecordType | None = None

    def _record_type(self) -> RecordType:
        return self.record_type or _CATEGORY_TO_TYPE.get(self.category, RecordType.TENDER)

    def _pages(self) -> list[str]:
        raw = os.environ.get(self.pages_env, "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            result = self.client.fetch(url)
            yield RawPayload(source_url=str(result.url), content=result.content, ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")
        seen_urls: set[str] = set()
        record_type = self._record_type()

        for item in soup.find_all(list(self.item_tags)):
            link = item.find("a", href=True)
            if not link:
                continue

            text = clean_text(item.get_text(" ", strip=True))
            if not text or len(text) < self.min_text_len:
                continue

            href = link["href"]
            source_url = self._abs_url(href)
            if source_url in seen_urls:
                continue
            seen_urls.add(source_url)

            date_match = _DATE_EXTRACTOR.search(text)
            dt = parse_date(date_match.group(1)) if date_match else None
            published = to_utc_iso(dt) if dt else None

            if "id=" in href:
                natural_key = href.split("id=", 1)[1].split("&", 1)[0]
            else:
                natural_key = hashlib.sha1(text.encode("utf-8")).hexdigest()[:16]

            # Provenance extras kept alongside the typed envelope: a thin listing
            # carries little structure, so `buyer`/`published_at` stay for back-compat
            # and for the not-yet-projected record types (jobs, assets, …).
            extras: dict = {"buyer": self.buyer, "published_at": published, "source_url": source_url}
            if self.extra_payload:
                extras.update(self.extra_payload)

            canonical = CanonicalPayload(
                record_type=record_type,
                category=self.category,
                title=text,
                authority=Authority(name=self.buyer) if self.buyer else None,
                tender=TenderDetail(announced_at=published, status="announced")
                if record_type == RecordType.TENDER else None,
                **extras,
            )
            yield make_record(
                source=self.id,
                natural_key=natural_key,
                source_url=source_url,
                fetched_at=payload.fetched_at,
                payload=canonical,
            )

    def _abs_url(self, href: str) -> str:
        if href.startswith("http"):
            return href
        return f"{self.base_url}/{href.lstrip('/')}"
