"""MVR donations register — uncover influence and off-the-books payments.

Parses the donation reports published by the Ministry of Interior.
"""

from __future__ import annotations

import hashlib
import os
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..contract import CanonicalPayload, IngestRecord, RecordType, make_record
from ..encoding import decode_bytes
from ..normalize import clean_text, parse_date, parse_money, to_utc_iso
from ..spheres import CATEGORY_DONATIONS, SPHERE_POLICE
from .base import RawPayload, Source


class MvrDonationsSource(Source):
    id = "mvr_donations"
    raw_ext = "html"
    sphere = SPHERE_POLICE
    category = CATEGORY_DONATIONS

    def _pages(self) -> list[str]:
        raw = os.environ.get("MVR_DONATIONS_PAGES", "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            result = self.client.fetch(url)
            yield RawPayload(source_url=str(result.url), content=result.content, ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")
        
        # Donation reports are often in tables
        for table in soup.find_all("table"):
            headers = self._table_headers(table)
            for row in table.find_all("tr"):
                cells = row.find_all(["td"])
                if not cells or len(cells) < 3:
                    continue
                
                values = [clean_text(c.get_text(" ", strip=True)) for c in cells]
                if not any(values):
                    continue

                if headers and len(headers) == len(values):
                    row_data = dict(zip(headers, values))
                else:
                    row_data = {f"col_{i}": v for i, v in enumerate(values)}

                # Try to extract amount and date
                amount, currency = parse_money(row_data.get("Стойност") or row_data.get("col_3"))
                dt = parse_date(row_data.get("Дата") or row_data.get("col_4"))
                
                natural_key = hashlib.sha1(
                    "|".join(f"{k}={v}" for k, v in sorted(row_data.items())).encode("utf-8")
                ).hexdigest()[:16]

                donor = row_data.get("Дарител") or row_data.get("col_1")
                subject = row_data.get("Предмет") or row_data.get("col_2")
                canonical = CanonicalPayload(
                    record_type=RecordType.DONATION,
                    category=CATEGORY_DONATIONS,
                    title=subject or donor or "(дарение)",
                    donor=donor,
                    subject=subject,
                    value={"amount": amount, "currency": currency or "BGN"},
                    donated_at=to_utc_iso(dt) if dt else None,
                    raw_row=row_data,
                )

                yield make_record(
                    source=self.id,
                    natural_key=natural_key,
                    source_url=payload.source_url,
                    fetched_at=payload.fetched_at,
                    payload=canonical,
                )

    @staticmethod
    def _table_headers(table) -> list[str]:
        head_row = table.find("tr")
        if not head_row:
            return []
        ths = head_row.find_all(["th", "td"])
        return [clean_text(th.get_text(" ", strip=True)) for th in ths]
