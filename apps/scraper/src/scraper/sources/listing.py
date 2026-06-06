"""Shared HTML listing parser for sphere-specific procurement, jobs, and asset pages."""

from __future__ import annotations

import hashlib
import os
import re
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..contract import IngestRecord
from ..encoding import decode_bytes
from ..normalize import clean_text, parse_date, to_utc_iso
from ..spheres import CATEGORY_PROCUREMENT
from .base import RawPayload, Source

_DATE_EXTRACTOR = re.compile(r"(\d{2}\.\d{2}\.\d{4})")


class ListingSource(Source):
    """Parse HTML pages that list linked items (tenders, jobs, asset sales)."""

    raw_ext = "html"
    category = CATEGORY_PROCUREMENT
    pages_env: str = ""
    buyer: str = ""
    item_tags: tuple[str, ...] = ("li", "div", "p", "tr", "h3")
    min_text_len: int = 0
    extra_payload: dict | None = None

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

            if "id=" in href:
                natural_key = href.split("id=", 1)[1].split("&", 1)[0]
            else:
                natural_key = hashlib.sha1(text.encode("utf-8")).hexdigest()[:16]

            payload_data = {
                "title": text,
                "published_at": to_utc_iso(dt) if dt else None,
                "buyer": self.buyer,
                "source_url": source_url,
            }
            if self.extra_payload:
                payload_data.update(self.extra_payload)

            yield IngestRecord(
                source=self.id,
                natural_key=natural_key,
                source_url=source_url,
                fetched_at=payload.fetched_at,
                payload=payload_data,
            )

    def _abs_url(self, href: str) -> str:
        if href.startswith("http"):
            return href
        return f"{self.base_url}/{href.lstrip('/')}"
