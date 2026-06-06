"""IVSS magistrate property declarations — cross-check wealth vs. official income."""

from __future__ import annotations

import hashlib
import os
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..contract import IngestRecord
from ..encoding import decode_bytes
from ..normalize import clean_text, parse_date, to_utc_iso
from ..spheres import CATEGORY_PAYMENTS, SPHERE_JUDICIARY
from .base import RawPayload, Source


class IvssDeclarationsSource(Source):
    id = "ivss_declarations"
    raw_ext = "html"
    sphere = SPHERE_JUDICIARY
    category = CATEGORY_PAYMENTS

    def _pages(self) -> list[str]:
        raw = os.environ.get("IVSS_DECLARATIONS_PAGES", "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            result = self.client.fetch(url)
            yield RawPayload(source_url=str(result.url), content=result.content, ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")

        for table in soup.find_all("table"):
            headers = self._table_headers(table)
            for row in table.find_all("tr"):
                cells = row.find_all(["td"])
                if not cells or len(cells) < 2:
                    continue

                values = [clean_text(c.get_text(" ", strip=True)) for c in cells]
                if not any(values):
                    continue

                if headers and len(headers) == len(values):
                    row_data = dict(zip(headers, values))
                else:
                    row_data = {f"col_{i}": v for i, v in enumerate(values)}

                magistrate = (
                    row_data.get("Магистрат")
                    or row_data.get("Име")
                    or row_data.get("col_0")
                )
                if not magistrate or magistrate.lower() in {"магистрат", "име"}:
                    continue

                dt = parse_date(
                    row_data.get("Година")
                    or row_data.get("Дата")
                    or row_data.get("col_3")
                )

                natural_key = hashlib.sha1(
                    "|".join(f"{k}={v}" for k, v in sorted(row_data.items())).encode("utf-8")
                ).hexdigest()[:16]

                payload_data = {
                    "magistrate": magistrate,
                    "position": row_data.get("Длъжност") or row_data.get("col_1"),
                    "court": row_data.get("Съд/прокуратура") or row_data.get("col_2"),
                    "declared_at": to_utc_iso(dt) if dt else None,
                    "raw_row": row_data,
                }

                yield IngestRecord(
                    source=self.id,
                    natural_key=natural_key,
                    source_url=payload.source_url,
                    fetched_at=payload.fetched_at,
                    payload=payload_data,
                )

    @staticmethod
    def _table_headers(table) -> list[str]:
        head_row = table.find("tr")
        if not head_row:
            return []
        ths = head_row.find_all(["th", "td"])
        return [clean_text(th.get_text(" ", strip=True)) for th in ths]
