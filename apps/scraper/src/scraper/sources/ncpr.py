"""NCPR drug ceiling prices — the healthcare overpricing benchmark.

Published as open data on data.egov.bg / ncpr.bg. Contains the maximum allowed
prices for drugs reimbursed by the state.
"""

from __future__ import annotations

import csv
import hashlib
import io
import os
from collections.abc import Iterator

from ..contract import IngestRecord
from ..encoding import decode_bytes
from ..normalize import (
    clean_text,
    normalize_company_name,
    parse_money,
)
from ..spheres import SPHERE_HEALTHCARE
from .base import RawPayload, Source

# Header hints for the NCPR register
_HEADER_HINTS: dict[str, tuple[str, ...]] = {
    "inn": ("inn", "атс", "международно", "непатентно"),
    "product": ("търговско", "наименование", "продукт", "product"),
    "packaging": ("опаковка", "количество", "packaging"),
    "price_ceiling": ("пределна", "цена", "ceiling"),
    "price_reimbursement": ("референтна", "реимбурсиране", "reimbursement"),
    "holder": ("притежател", "разрешение", "holder"),
}


class NcprSource(Source):
    id = "ncpr"
    raw_ext = "csv"
    sphere = SPHERE_HEALTHCARE

    def _csv_urls(self) -> list[str]:
        raw = os.environ.get("NCPR_CSV_URLS", "")
        return [u.strip() for u in raw.split(",") if u.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._csv_urls():
            result = self.client.fetch(url)
            yield RawPayload(source_url=url, content=result.content, ext="csv")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        text = decode_bytes(payload.content)
        # Sniff delimiter
        head = text.splitlines()[0] if text.splitlines() else ""
        delimiter = ";" if head.count(";") >= head.count(",") else ","
        
        reader = csv.DictReader(io.StringIO(text), delimiter=delimiter)
        if not reader.fieldnames:
            self.skip(payload.source_url, "empty CSV / no header")
            return

        colmap = self._map_columns(reader.fieldnames)
        for idx, row in enumerate(reader):
            record = self._row_to_record(row, colmap, payload, idx)
            if record is not None:
                yield record

    def _row_to_record(self, row, colmap, payload, idx) -> IngestRecord | None:
        def col(field: str) -> str:
            header = colmap.get(field)
            return clean_text(row.get(header, "")) if header else ""

        product = col("product")
        if not product:
            return None

        # Natural key is a hash of the product + packaging + holder
        natural_key = hashlib.sha1(
            f"{product}|{col('packaging')}|{col('holder')}".encode("utf-8")
        ).hexdigest()[:16]

        ceiling_amt, ceiling_cur = parse_money(col("price_ceiling"))
        reimb_amt, reimb_cur = parse_money(col("price_reimbursement"))

        payload_data = {
            "inn": col("inn"),
            "product": product,
            "packaging": col("packaging"),
            "price_ceiling": {"amount": ceiling_amt, "currency": ceiling_cur or "BGN"},
            "price_reimbursement": {"amount": reimb_amt, "currency": reimb_cur or "BGN"},
            "holder": normalize_company_name(col("holder")),
            "raw_row": row,
        }

        return IngestRecord(
            source=self.id,
            natural_key=natural_key,
            source_url=payload.source_url,
            fetched_at=payload.fetched_at,
            payload=payload_data,
        )

    @staticmethod
    def _map_columns(headers: list[str]) -> dict[str, str]:
        colmap: dict[str, str] = {}
        for field, hints in _HEADER_HINTS.items():
            for header in headers:
                low = (header or "").lower()
                if any(hint in low for hint in hints):
                    colmap[field] = header
                    break
        return colmap
