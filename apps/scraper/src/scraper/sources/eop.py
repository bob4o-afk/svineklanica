"""ЦАИС ЕОП — app.eop.bg (the modern central procurement system).

The search UI is JS-rendered, so we render result pages with Playwright (the
``browser`` extra) and parse the rendered HTML. Configure result URLs with
``EOP_PAGES`` (comma-separated). :meth:`parse` works on plain HTML, so it is
unit-tested offline against saved fixtures without a browser.
"""

from __future__ import annotations

import hashlib
import os
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..browser import render
from ..contract import IngestRecord
from ..encoding import decode_bytes
from ..normalize import clean_text
from .base import RawPayload, Source


class EopSource(Source):
    id = "eop"
    raw_ext = "html"

    def _pages(self) -> list[str]:
        raw = os.environ.get("EOP_PAGES", "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            # SSRF guard: ensure the host is allow-listed before rendering.
            self.client._check_allowed(url)  # noqa: SLF001 - intentional guard reuse
            rendered = render(url, user_agent=self.config.user_agent,
                              wait_selector="table, .search-result, .result-item")
            yield RawPayload(source_url=url, content=rendered.html.encode("utf-8"),
                             ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")
        items = soup.select(".search-result, .result-item, tr")
        emitted = 0
        for item in items:
            text = clean_text(item.get_text(" ", strip=True))
            if not text:
                continue
            link = item.find("a", href=True)
            source_url = self._abs_url(link["href"]) if link else payload.source_url
            natural_key = self._natural_key(source_url, text, emitted)
            fields = {clean_text(c.get_text(" ", strip=True))
                      for c in item.find_all(["td", "span", "div"])}
            yield IngestRecord(
                source=self.id,
                natural_key=natural_key,
                source_url=source_url,
                fetched_at=payload.fetched_at,
                payload={"text": text, "fields": [f for f in fields if f]},
            )
            emitted += 1

    def _abs_url(self, href: str) -> str:
        if href.startswith("http"):
            return href
        return f"{self.base_url}/{href.lstrip('/')}"

    @staticmethod
    def _natural_key(source_url: str, text: str, idx: int) -> str:
        if "id=" in source_url:
            return source_url.split("id=", 1)[1].split("&", 1)[0]
        return "eop:" + hashlib.sha1(f"{source_url}|{text}".encode("utf-8")).hexdigest()[:16]
