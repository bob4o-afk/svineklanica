"""VSS (Висш съдебен съвет) procurement notices — judiciary governing body.

Parses the "Buyer Profile" of the Supreme Judicial Council.
"""

from __future__ import annotations

import hashlib
import os
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..contract import IngestRecord
from ..encoding import decode_bytes
from ..normalize import clean_text
from ..spheres import SPHERE_JUDICIARY
from .base import RawPayload, Source


class VssSource(Source):
    id = "vss"
    raw_ext = "html"
    sphere = SPHERE_JUDICIARY

    def _pages(self) -> list[str]:
        raw = os.environ.get("VSS_PAGES", "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            result = self.client.fetch(url)
            yield RawPayload(source_url=str(result.url), content=result.content, ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")
        emitted = 0
        
        # VSS profile often uses tables for procedures
        for table in soup.find_all("table"):
            headers = self._table_headers(table)
            for row in table.find_all("tr"):
                cells = row.find_all(["td"])
                if not cells:
                    continue
                
                values = [clean_text(c.get_text(" ", strip=True)) for c in cells]
                if not any(values):
                    continue
                
                # Try to find a link to the procedure detail
                link = row.find("a", href=True)
                source_url = self._abs_url(link["href"]) if link else payload.source_url
                
                if headers and len(headers) == len(values):
                    row_data = dict(zip(headers, values))
                else:
                    row_data = {f"col_{i}": v for i, v in enumerate(values)}
                
                # Natural key from ID/Number column or hash
                natural_key = self._natural_key(row_data, source_url, emitted)
                
                yield IngestRecord(
                    source=self.id,
                    natural_key=natural_key,
                    source_url=source_url,
                    fetched_at=payload.fetched_at,
                    payload={"row": row_data, "buyer": "ВСС"},
                )
                emitted += 1

    @staticmethod
    def _table_headers(table) -> list[str]:
        head_row = table.find("tr")
        if not head_row:
            return []
        ths = head_row.find_all(["th", "td"]) # sometimes th is not used
        # If the first row looks like data, it's not headers
        if any(c.find("a") for c in ths):
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
        return f"vss:{digest}"
