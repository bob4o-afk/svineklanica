"""АОП / РОП — Регистър на обществените поръчки (aop.bg).

Historical depth, pre-ЕОП notices. The register is an HTML table, so we parse
listing pages with BeautifulSoup. Cyrillic on the legacy register is cp1251, so
we decode bytes defensively (handled by the client / encoding helper).

Configure listing page URLs with ``AOP_PAGES`` (comma-separated). :meth:`parse`
extracts every ``<table>`` row generically and maps headered columns into the
payload, so it degrades gracefully if the markup shifts.
"""

from __future__ import annotations

import hashlib
import os
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..contract import (
    Authority,
    CanonicalPayload,
    IngestRecord,
    RecordType,
    make_record,
)
from ..encoding import decode_bytes
from ..normalize import best_row_authority, best_row_title, clean_text
from ..spheres import CATEGORY_PROCUREMENT
from .base import RawPayload, Source


class AopSource(Source):
    id = "aop"
    raw_ext = "html"

    def _pages(self) -> list[str]:
        raw = os.environ.get("AOP_PAGES", "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            result = self.client.fetch(url)
            yield RawPayload(source_url=str(result.url), content=result.content, ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        # Decode ourselves (chardet -> cp1251/utf-8) so bs4 doesn't mis-trust a
        # legacy meta charset and produce mojibake.
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")
        emitted = 0
        for table in soup.find_all("table"):
            headers = self._table_headers(table)
            for row in table.find_all("tr"):
                cells = row.find_all(["td"])
                if not cells:
                    continue
                values = [clean_text(c.get_text(" ", strip=True)) for c in cells]
                if not any(values):
                    continue
                record = self._row_record(headers, values, row, payload, emitted)
                emitted += 1
                yield record

    def _row_record(self, headers, values, row, payload, idx) -> IngestRecord:
        if headers and len(headers) == len(values):
            row_data = dict(zip(headers, values))
        else:
            row_data = {f"col_{i}": v for i, v in enumerate(values)}

        link = row.find("a", href=True)
        source_url = self._abs_url(link["href"]) if link else payload.source_url
        natural_key = self._natural_key(row_data, source_url, idx)

        authority_name = best_row_authority(row_data)
        canonical = CanonicalPayload(
            record_type=RecordType.TENDER,
            category=CATEGORY_PROCUREMENT,
            title=best_row_title(row_data),
            authority=Authority(name=authority_name) if authority_name else None,
            # Legacy register markup shifts — keep the whole row for provenance.
            row=row_data,
        )
        return make_record(
            source=self.id,
            natural_key=natural_key,
            source_url=source_url,
            fetched_at=payload.fetched_at,
            payload=canonical,
        )

    @staticmethod
    def _table_headers(table) -> list[str]:
        head_row = table.find("tr")
        if not head_row:
            return []
        ths = head_row.find_all("th")
        if not ths:
            return []
        return [clean_text(th.get_text(" ", strip=True)) for th in ths]

    def _abs_url(self, href: str) -> str:
        if href.startswith("http"):
            return href
        return f"{self.base_url}/{href.lstrip('/')}"

    @staticmethod
    def _natural_key(row_data: dict, source_url: str, idx: int) -> str:
        for key, value in row_data.items():
            low = key.lower()
            if value and ("номер" in low or "id" in low or "решение" in low):
                return value
        if "id=" in source_url:
            return source_url.split("id=", 1)[1].split("&", 1)[0]
        digest = hashlib.sha1(
            "|".join(f"{k}={v}" for k, v in sorted(row_data.items())).encode("utf-8")
        ).hexdigest()[:16]
        return f"aop:{digest}"
