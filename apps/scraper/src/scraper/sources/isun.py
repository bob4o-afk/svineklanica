"""ИСУН 2020 — 2020.eufunds.bg (EU-funds beneficiaries & contracts).

Huge punch (EU money → procurement winners), but behind a WAF that 403s
non-browser clients, so we render with Playwright (the ``browser`` extra).
Configure beneficiary/contract listing URLs with ``ISUN_PAGES``. :meth:`parse`
operates on plain HTML for offline testing.

Note: 2007–2013 registries lack unique company ids and have misspelled names —
we keep names + any EIK we find; entity resolution happens downstream.
"""

from __future__ import annotations

import hashlib
import os
from collections.abc import Iterator

from bs4 import BeautifulSoup

from ..browser import render
from ..contract import CanonicalPayload, IngestRecord, RecordType, make_record
from ..encoding import decode_bytes
from ..normalize import clean_text, normalize_company_name, parse_money
from ..spheres import CATEGORY_PROJECTS
from .base import RawPayload, Source


class IsunSource(Source):
    id = "isun"
    raw_ext = "html"

    def _pages(self) -> list[str]:
        raw = os.environ.get("ISUN_PAGES", "")
        return [p.strip() for p in raw.split(",") if p.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        for url in self._pages():
            self.client._check_allowed(url)  # noqa: SLF001 - intentional guard reuse
            rendered = render(url, user_agent=self.config.user_agent,
                              wait_selector="table, .beneficiary, .project")
            yield RawPayload(source_url=url, content=rendered.html.encode("utf-8"),
                             ext="html")

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        soup = BeautifulSoup(decode_bytes(payload.content), "lxml")
        rows = soup.select("table tr, .project, .beneficiary")
        emitted = 0
        for row in rows:
            cells = [clean_text(c.get_text(" ", strip=True))
                     for c in row.find_all(["td", "div", "span"])]
            cells = [c for c in cells if c]
            if not cells:
                continue
            name = cells[0]
            amount, currency = self._find_amount(cells)
            natural_key = "isun:" + hashlib.sha1(
                "|".join(cells).encode("utf-8")
            ).hexdigest()[:16]
            canonical = CanonicalPayload(
                record_type=RecordType.PROJECT,
                category=CATEGORY_PROJECTS,
                title=name,
                # EU-funds rows lack clean ids/structure — keep what we found.
                beneficiary=name,
                beneficiary_normalized=normalize_company_name(name),
                grant={"amount": amount, "currency": currency},
                fields=cells,
            )
            yield make_record(
                source=self.id,
                natural_key=natural_key,
                source_url=payload.source_url,
                fetched_at=payload.fetched_at,
                payload=canonical,
            )
            emitted += 1

    @staticmethod
    def _find_amount(cells: list[str]) -> tuple[float | None, str | None]:
        for cell in cells:
            amount, currency = parse_money(cell)
            if amount is not None and amount > 0:
                return amount, currency
        return None, None
