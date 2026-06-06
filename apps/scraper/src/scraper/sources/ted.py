"""TED — Tenders Electronic Daily (ted.europa.eu).

The best structured source: EU-wide notices incl. above-threshold Bulgarian
tenders, exposed via the official Search API v3 (JSON). We page through notices
whose buyer country is Bulgaria.

The expert query is configurable via ``TED_QUERY`` (defaults to buyer country
BGR). API host ``api.ted.europa.eu`` is covered by the ``ted.europa.eu``
allow-list entry (subdomain match).
"""

from __future__ import annotations

import json
import os
from collections.abc import Iterator

from ..contract import (
    Authority,
    CanonicalPayload,
    IngestRecord,
    RecordType,
    TenderDetail,
    make_record,
)
from ..normalize import clean_text, parse_money
from ..spheres import CATEGORY_PROCUREMENT
from .base import RawPayload, Source

_API_URL = "https://api.ted.europa.eu/v3/notices/search"
_DEFAULT_QUERY = "buyer-country=BGR SORT BY publication-date DESC"
_FIELDS = [
    "publication-number",
    "notice-title",
    "buyer-name",
    "buyer-country",
    "notice-type",
    "publication-date",
    "deadline-receipt-request",
    "total-value",
    "classification-cpv",
    "links",
]


class TedSource(Source):
    id = "ted"
    raw_ext = "json"

    def _query(self) -> str:
        return os.environ.get("TED_QUERY", _DEFAULT_QUERY)

    def _page_size(self) -> int:
        try:
            return int(os.environ.get("TED_PAGE_SIZE", "100"))
        except ValueError:
            return 100

    def fetch(self) -> Iterator[RawPayload]:
        # TED Search API v3 requires POST with a JSON body.
        body = {
            "query": self._query(),
            "fields": _FIELDS,
            "limit": self._page_size(),
            "page": 1,
            "scope": "ALL",
        }
        result = self.client.post(_API_URL, json=body)
        yield RawPayload(source_url="https://ted.europa.eu/en/", content=result.content,
                         ext="json")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        try:
            doc = json.loads(payload.content.decode("utf-8"))
        except (json.JSONDecodeError, UnicodeDecodeError) as exc:
            self.skip(payload.source_url, f"bad JSON: {exc}")
            return

        notices = doc.get("notices") or doc.get("results") or []
        for notice in notices:
            if not isinstance(notice, dict):
                continue
            pub = self._first(notice.get("publication-number"))
            if not pub:
                self.skip(str(notice)[:60], "no publication-number")
                continue

            amount, currency = parse_money(self._first(notice.get("total-value")))
            source_url = self._detail_url(notice, pub)
            buyer = clean_text(self._first(notice.get("buyer-name")))
            cpv = self._first(notice.get("classification-cpv"))

            canonical = CanonicalPayload(
                record_type=RecordType.TENDER,
                # sphere left unset — base.py infers it from authority + CPV (one place).
                category=CATEGORY_PROCUREMENT,
                title=clean_text(self._first(notice.get("notice-title"))),
                authority=Authority(name=buyer) if buyer else None,
                tender=TenderDetail(
                    cpv_code=str(cpv) if cpv else None,
                    value=amount,
                    currency=currency,
                    status="announced",
                    announced_at=self._first(notice.get("publication-date")),
                    deadline_at=self._first(notice.get("deadline-receipt-request")),
                ),
            )
            yield make_record(
                source=self.id,
                natural_key=str(pub),
                source_url=source_url,
                fetched_at=payload.fetched_at,
                payload=canonical,
            )

    @staticmethod
    def _first(value: object) -> object:
        """TED returns many fields as language maps or lists; take the first."""
        if isinstance(value, list):
            return value[0] if value else None
        if isinstance(value, dict):
            # Prefer Bulgarian — scraped content stays Bulgarian (scraping.md §3).
            for key in ("bul", "bg", "eng", "en"):
                if key in value:
                    inner = value[key]
                    return inner[0] if isinstance(inner, list) and inner else inner
            return next(iter(value.values()), None)
        return value

    def _detail_url(self, notice: dict, pub: str) -> str:
        links = notice.get("links")
        if isinstance(links, dict):
            for candidate in links.values():
                if isinstance(candidate, str) and candidate.startswith("http"):
                    return candidate
        return f"https://ted.europa.eu/en/notice/-/detail/{pub}"
