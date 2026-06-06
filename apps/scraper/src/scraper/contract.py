"""The scraper -> Laravel seam contract.

This is the ONE piece of logic committed before the event, because it *is* the
boundary definition, not business logic. The Python scraper emits `IngestRecord`
lines (NDJSON); `php artisan ingest:run --source=<x>` reads them and upserts on
`natural_key`. Keep this in lockstep with the Laravel ingest mapping and SOURCES.md
(see /.claude/rules/scraping.md §2). Bump `schema_version` on a breaking change.

## The canonical payload (schema v2)

Sources are diverse — TED is a tender, СЕБРА is a payment, МВР publishes asset
disposals, donations, job competitions… so a single flat "tender" shape can't
serve them all (and the older v1 one didn't — Laravel only knew how to map a
tender). v2 is **envelope + typed detail**:

  * a **shared envelope** every record carries — `record_type`, `sphere`,
    `category`, `title`, `description`, `authority`, `winner`;
  * **exactly one typed block** keyed by `record_type` (`tender`, `payment`, …).

A source fills the envelope once and its own typed block; Laravel dispatches on
`record_type` to the matching mapper (`modules/Procurement/Ingest/*Mapper`). Add a
new source type later = one new typed block here + one new mapper there, nothing
else. `sphere`/`category` travel as the Bulgarian strings the scraper already
computes (``spheres.py``); Laravel maps those strings → its int-backed enums
instead of re-deriving them.
"""

from __future__ import annotations

from datetime import datetime
from enum import Enum

from pydantic import BaseModel, ConfigDict, Field

SCHEMA_VERSION = 2


class RecordType(str, Enum):
    """What KIND of record this is — drives the Laravel mapper (one per type)."""

    TENDER = "tender"            # обществена поръчка (TED, ЕОП, АОП, egov…)
    PAYMENT = "payment"          # бюджетно плащане (СЕБРА)
    ASSET = "asset"              # разпореждане с актив
    DONATION = "donation"        # дарение
    JOB = "job"                  # конкурс за работа
    CONCESSION = "concession"    # концесия
    DECLARATION = "declaration"  # имуществена декларация
    AUDIT = "audit"              # одит
    PROJECT = "project"          # инфраструктурен проект (eufunds / АПИ)
    REFERENCE = "reference"      # референтни данни (напр. НЦПР пределни цени) — benchmark, not a flagged record


class Authority(BaseModel):
    """A contracting authority / public spender (the buyer side)."""

    model_config = ConfigDict(extra="forbid")

    name: str
    eik: str | None = None
    region: str | None = None
    lat: float | None = None
    lng: float | None = None
    source_url: str | None = None


class Company(BaseModel):
    """A company (the winner / recipient side). Identity unifies on EIK, not name."""

    model_config = ConfigDict(extra="forbid")

    name: str
    eik: str | None = None
    address: str | None = None
    owner_name: str | None = None
    phone: str | None = None
    source_url: str | None = None


class LineItem(BaseModel):
    """One priced line item of a tender (feeds the price-over-time snapshots)."""

    model_config = ConfigDict(extra="forbid")

    description: str
    quantity: float | None = None
    unit: str | None = None
    unit_price: float | None = None
    currency: str | None = None
    vat_included: bool | None = None
    source_url: str | None = None


class TenderDetail(BaseModel):
    """The `tender` typed block (record_type == "tender")."""

    model_config = ConfigDict(extra="forbid")

    cpv_code: str | None = None
    value: float | None = None
    currency: str | None = None
    vat_included: bool | None = None
    #: announced | open | awarded | cancelled | terminated
    status: str | None = None
    announced_at: str | None = None
    deadline_at: str | None = None
    awarded_at: str | None = None
    cancelled_at: str | None = None
    items: list[LineItem] = Field(default_factory=list)


class PaymentDetail(BaseModel):
    """The `payment` typed block (record_type == "payment")."""

    model_config = ConfigDict(extra="forbid")

    spender: str
    recipient: str | None = None
    amount: float | None = None
    currency: str | None = None
    paid_at: str | None = None
    purpose: str | None = None


class CanonicalPayload(BaseModel):
    """The normalized `payload` of an :class:`IngestRecord` (schema v2).

    ``extra="allow"`` on purpose: a not-yet-modeled record type (asset, donation,
    …) can still carry its raw fields through to provenance until it gets a typed
    block + a Laravel mapper. The well-known blocks below are validated.
    """

    model_config = ConfigDict(extra="allow")

    record_type: RecordType
    sphere: str | None = None
    category: str
    title: str
    description: str | None = None
    authority: Authority | None = None
    winner: Company | None = None
    tender: TenderDetail | None = None
    payment: PaymentDetail | None = None

    def as_dict(self) -> dict:
        """Serialize to the plain dict carried in the NDJSON line (no None noise)."""
        return self.model_dump(mode="json", exclude_none=True)


class IngestRecord(BaseModel):
    """One normalized record written to ./storage/ingest/normalized/<source>.ndjson."""

    model_config = ConfigDict(extra="forbid")

    source: str = Field(..., description="Source id, e.g. 'ted', 'egov', 'aop'.")
    natural_key: str = Field(..., description="Stable per-source key for idempotent upsert (TED notice id, registry no, EIK).")
    source_url: str = Field(..., description="A URL a human can open to verify this record.")
    fetched_at: datetime = Field(..., description="When we fetched it (ISO-8601, UTC).")
    schema_version: int = Field(default=SCHEMA_VERSION)
    payload: dict = Field(..., description="Normalized, source-specific fields. Bulgarian text stays Bulgarian (UTF-8).")

    def to_ndjson_line(self) -> str:
        """Serialize to a single UTF-8 NDJSON line (Cyrillic preserved)."""
        return self.model_dump_json()


def make_record(
    *,
    source: str,
    natural_key: str,
    source_url: str,
    fetched_at: datetime,
    payload: CanonicalPayload,
) -> IngestRecord:
    """Build an :class:`IngestRecord` from a validated canonical payload.

    The one blessed way to emit a v2 record: validates the envelope + typed block,
    then flattens to the dict the NDJSON line carries.
    """
    return IngestRecord(
        source=source,
        natural_key=natural_key,
        source_url=source_url,
        fetched_at=fetched_at,
        payload=payload.as_dict(),
    )
