"""data.egov.bg — National Open Data Portal (CKAN fork).

The cleanest legal source: an open REST API over hundreds of agency datasets,
including procurement dumps. We read resource datasets (configured by URI) via
the ``resourceData`` endpoint and turn each row into an IngestRecord.

Configure which resources to pull with ``EGOV_RESOURCES`` (comma-separated
resource URIs/ids). The dataset shape varies, so :meth:`parse` accepts the two
common envelopes:
- data.egov custom: ``{"success": true, "data": [ {..}, .. ]}``
- CKAN datastore:    ``{"result": {"records": [ {..}, .. ]}}``
"""

from __future__ import annotations

import hashlib
import json
import os
from collections.abc import Iterator

from ..contract import (
    Authority,
    CanonicalPayload,
    IngestRecord,
    RecordType,
    make_record,
)
from ..normalize import best_row_authority, best_row_title, clean_text
from ..spheres import CATEGORY_PROCUREMENT
from .base import RawPayload, Source

# Column names (lower-cased) we try, in order, for a stable natural key.
_KEY_FIELDS = ("id", "uri", "number", "номер", "registry_number", "doc_id", "_id")


class EgovSource(Source):
    id = "egov"
    raw_ext = "json"

    def _resource_uris(self) -> list[str]:
        raw = os.environ.get("EGOV_RESOURCES", "")
        return [r.strip() for r in raw.split(",") if r.strip()]

    def fetch(self) -> Iterator[RawPayload]:
        uris = self._resource_uris()
        if not uris:
            return
        for uri in uris:
            # data.egov.bg is a custom (non-CKAN-core) API: POST getResourceData
            # with the resource_uri in the JSON body. Confirmed live.
            api_url = f"{self.base_url}/api/getResourceData"
            result = self.client.post(api_url, json={"resource_uri": uri})
            human_url = f"{self.base_url}/data/view/{uri}"
            yield RawPayload(
                source_url=human_url,
                content=result.content,
                ext="json",
                meta={"resource_uri": uri},
            )

    def parse(self, payload: RawPayload) -> Iterator[IngestRecord]:
        try:
            doc = json.loads(payload.content.decode("utf-8"))
        except (json.JSONDecodeError, UnicodeDecodeError) as exc:
            self.skip(payload.source_url, f"bad JSON: {exc}")
            return

        rows = self._extract_rows(doc)
        resource_uri = payload.meta.get("resource_uri", "")
        for idx, row in enumerate(rows):
            if not isinstance(row, dict):
                self.skip(f"{resource_uri}#{idx}", "row is not an object")
                continue
            natural_key = self._natural_key(row, resource_uri, idx)
            cleaned = {k: clean_text(v) if isinstance(v, str) else v for k, v in row.items()}
            authority_name = best_row_authority(cleaned)
            canonical = CanonicalPayload(
                record_type=RecordType.TENDER,
                category=CATEGORY_PROCUREMENT,
                title=best_row_title(cleaned),
                authority=Authority(name=authority_name) if authority_name else None,
                # Open-data columns vary wildly — keep the whole row for provenance.
                resource_uri=resource_uri,
                row=cleaned,
            )
            yield make_record(
                source=self.id,
                natural_key=natural_key,
                source_url=payload.source_url,
                fetched_at=payload.fetched_at,
                payload=canonical,
            )

    @staticmethod
    def _extract_rows(doc: object) -> list:
        if isinstance(doc, list):
            return doc
        if isinstance(doc, dict):
            if isinstance(doc.get("data"), list):
                return doc["data"]
            result = doc.get("result")
            if isinstance(result, dict) and isinstance(result.get("records"), list):
                return result["records"]
            if isinstance(doc.get("records"), list):
                return doc["records"]
        return []

    @staticmethod
    def _natural_key(row: dict, resource_uri: str, idx: int) -> str:
        lower = {str(k).lower(): v for k, v in row.items()}
        for field_name in _KEY_FIELDS:
            value = lower.get(field_name)
            if value not in (None, ""):
                return f"{resource_uri}:{value}"
        digest = hashlib.sha1(
            json.dumps(row, sort_keys=True, ensure_ascii=False).encode("utf-8")
        ).hexdigest()[:16]
        return f"{resource_uri}:{digest}"
