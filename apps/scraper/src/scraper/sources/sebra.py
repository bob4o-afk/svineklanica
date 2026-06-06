"""SEBRA — Система за електронни бюджетни разплащания (via minfin.bg / open data).

Actual budget payments by spenders. Powers the "delayed payments" detector:
contracted-vs-paid timeline. Published as CSV/report exports; configure URLs
with ``SEBRA_CSV_URLS`` (comma-separated). Semicolon/UTF-8 cp1251-safe parsing,
same discipline as the procurement CSVs.
"""

from __future__ import annotations

import csv
import hashlib
import io
import os
from collections.abc import Iterator

from ..contract import (
    Authority,
    CanonicalPayload,
    Company,
    IngestRecord,
    PaymentDetail,
    RecordType,
    make_record,
)
from ..encoding import decode_bytes
from ..normalize import clean_text, parse_date, parse_money, to_utc_iso
from ..spheres import CATEGORY_PAYMENTS
from .base import RawPayload, Source

_HEADER_HINTS: dict[str, tuple[str, ...]] = {
    "spender": ("разпоредител", "първостепенен", "spender", "институция", "ведомство"),
    "amount": ("сума", "плащане", "стойност", "amount", "value"),
    "date": ("дата", "период", "date"),
    "purpose": ("основание", "описание", "предмет", "purpose", "назначение"),
    "recipient": ("получател", "контрагент", "recipient", "доставчик"),
}


class SebraSource(Source):
    id = "sebra"
    raw_ext = "csv"
    category = CATEGORY_PAYMENTS  # нерегламентирани плащания (a payment, not a tender)

    def _csv_urls(self) -> list[str]:
        raw = os.environ.get("SEBRA_CSV_URLS", "")
        return [u.strip() for u in raw.split(",") if u.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._csv_urls():
            result = self.client.fetch(url)
            yield RawPayload(source_url=url, content=result.content, ext="csv")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        text = decode_bytes(payload.content)
        lines = text.splitlines()
        delimiter = ";" if lines and lines[0].count(";") >= lines[0].count(",") else ","
        reader = csv.DictReader(io.StringIO(text), delimiter=delimiter)
        if not reader.fieldnames:
            self.skip(payload.source_url, "empty CSV / no header")
            return

        colmap = {}
        for field, hints in _HEADER_HINTS.items():
            for header in reader.fieldnames:
                if any(h in (header or "").lower() for h in hints):
                    colmap[field] = header
                    break

        for idx, row in enumerate(reader):
            def col(field: str) -> str:
                header = colmap.get(field)
                return clean_text(row.get(header, "")) if header else ""

            amount, currency = parse_money(col("amount"))
            paid = parse_date(col("date"))
            joined = "|".join(f"{k}={v}" for k, v in sorted(row.items()))
            natural_key = hashlib.sha1(joined.encode("utf-8")).hexdigest()[:20]

            spender = col("spender")
            recipient = col("recipient")
            purpose = col("purpose")
            canonical = CanonicalPayload(
                record_type=RecordType.PAYMENT,
                category=CATEGORY_PAYMENTS,
                title=purpose or f"Плащане: {spender} → {recipient}".strip(),
                # spender → authority, recipient → company (denormalized side blocks).
                authority=Authority(name=spender) if spender else None,
                winner=Company(name=recipient) if recipient else None,
                payment=PaymentDetail(
                    spender=spender or "(неизвестен разпоредител)",
                    recipient=recipient or None,
                    amount=amount,
                    currency=currency or "BGN",
                    paid_at=to_utc_iso(paid) if paid else None,
                    purpose=purpose or None,
                ),
                # Keep the raw CSV row for provenance (extra field, allowed by v2).
                raw_row=row,
            )
            yield make_record(
                source=self.id,
                natural_key=natural_key,
                source_url=payload.source_url,
                fetched_at=payload.fetched_at,
                payload=canonical,
            )
