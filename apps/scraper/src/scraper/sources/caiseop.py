"""ЦАИС ЕОП procurement contracts — the serial-winner dataset.

Published as bulk ``caiseop_contracts<year>.csv`` on data.egov.bg (semicolon
delimited, usually UTF-8 but cp1251-safe). One row ≈ one awarded contract:
contracting authority, winner (name + EIK), value, CPV, dates, subject text.

Configure the CSV URLs with ``CAISEOP_CSV_URLS`` (comma-separated, full URLs on
the data.egov.bg domain). The header is matched fuzzily so a column rename
upstream doesn't break ingest; every original column is also kept in ``payload``.
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
    RecordType,
    TenderDetail,
    make_record,
)
from ..encoding import decode_bytes
from ..normalize import (
    clean_text,
    extract_cpv,
    is_valid_eik,
    normalize_company_name,
    parse_date,
    parse_money,
    to_utc_iso,
)
from ..spheres import CATEGORY_PROCUREMENT
from .base import RawPayload, Source

# Fuzzy header matching: field -> substrings (lower-cased) that identify it.
_HEADER_HINTS: dict[str, tuple[str, ...]] = {
    "number": ("номер", "идентификатор", "number", "id", "уникален"),
    "subject": ("предмет", "наименование", "subject", "описание"),
    "authority": ("възложител", "authority", "купувач", "buyer"),
    "authority_eik": ("еик на възложител", "възложител еик"),
    "winner": ("изпълнител", "winner", "contractor", "доставчик"),
    "winner_eik": ("еик на изпълнител", "изпълнител еик", "еик изпълнител"),
    "value": ("стойност", "цена", "value", "сума", "amount"),
    "cpv": ("cpv", "код"),
    "date": ("дата", "date", "сключен"),
}


class CaiseopSource(Source):
    id = "caiseop"
    raw_ext = "csv"

    def _csv_urls(self) -> list[str]:
        raw = os.environ.get("CAISEOP_CSV_URLS", "")
        return [u.strip() for u in raw.split(",") if u.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._csv_urls():
            result = self.client.fetch(url)
            yield RawPayload(source_url=url, content=result.content, ext="csv")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        text = decode_bytes(payload.content)
        delimiter = self._sniff_delimiter(text)
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

        number = col("number")
        natural_key = number or self._row_hash(row)

        amount, currency = parse_money(col("value"))
        signed = parse_date(col("date"))
        winner_eik = self._digits(col("winner_eik"))
        authority_eik = self._digits(col("authority_eik"))

        authority_name = col("authority")
        winner_name = col("winner")
        canonical = CanonicalPayload(
            record_type=RecordType.TENDER,
            category=CATEGORY_PROCUREMENT,
            title=col("subject") or "(без предмет)",
            authority=Authority(name=authority_name, eik=authority_eik or None)
            if authority_name else None,
            winner=Company(name=winner_name, eik=winner_eik or None)
            if winner_name else None,
            tender=TenderDetail(
                cpv_code=extract_cpv(col("cpv")) or None,
                value=amount,
                currency=currency or "BGN",
                awarded_at=to_utc_iso(signed) if signed else None,
                status="awarded",
            ),
            # Provenance extras: the registry number, EIK-validity flags (the
            # serial-winner detector wants both the EIK and whether it checksums),
            # normalized names for fuzzy grouping, and the raw row.
            number=number,
            authority_name_normalized=normalize_company_name(authority_name),
            authority_eik_valid=is_valid_eik(authority_eik) if authority_eik else False,
            winner_name_normalized=normalize_company_name(winner_name),
            winner_eik_valid=is_valid_eik(winner_eik) if winner_eik else False,
            raw_row=row,
        )
        return make_record(
            source=self.id,
            natural_key=natural_key,
            source_url=payload.source_url,
            fetched_at=payload.fetched_at,
            payload=canonical,
        )

    @staticmethod
    def _sniff_delimiter(text: str) -> str:
        head = text.splitlines()[0] if text.splitlines() else ""
        return ";" if head.count(";") >= head.count(",") else ","

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

    @staticmethod
    def _digits(value: str) -> str:
        return "".join(ch for ch in value if ch.isdigit())

    @staticmethod
    def _row_hash(row: dict) -> str:
        joined = "|".join(f"{k}={v}" for k, v in sorted(row.items()))
        return hashlib.sha1(joined.encode("utf-8")).hexdigest()[:16]
