"""A canonical, source-agnostic *view* over a raw ingest record's payload.

Every source has its own payload shape (see ``scraper.searchable``). The feature
extractors and agents must not care which source a record came from, so we map
each payload into a :class:`TenderView` of optional, normalized fields. Anything
missing stays ``None`` / empty and simply contributes no risk.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime
from typing import Any

from scraper.normalize import clean_text, extract_cpv, parse_date, parse_money
from scraper.searchable import build_document

_FIRST_KEYS_TITLE = ("title", "subject", "purpose", "name", "text", "description")
_FIRST_KEYS_BUYER = ("buyer", "authority", "contracting_authority", "spender", "възложител")
_FIRST_KEYS_WINNER = ("winner", "contractor", "supplier", "recipient", "beneficiary", "изпълнител")
_FIRST_KEYS_PROCEDURE = ("procedure_type", "procedure", "notice_type", "method")
_FIRST_KEYS_STATUS = ("status", "state", "lifecycle", "outcome")
_TEXT_KEYS = ("description", "spec", "specification", "requirements", "text", "conditions", "subject")


def _first(payload: dict, keys: tuple[str, ...]) -> Any:
    for key in keys:
        if key in payload and payload[key] not in (None, "", [], {}):
            return payload[key]
    return None


def _name_of(value: Any) -> str:
    """Entities can be a string or a dict with a 'name'."""
    if isinstance(value, dict):
        return clean_text(value.get("name") or value.get("title") or "")
    return clean_text(value) if isinstance(value, str) else ""


def _eik_of(value: Any) -> str | None:
    if isinstance(value, dict):
        for key in ("eik", "bulstat", "uic", "id", "ЕИК"):
            if value.get(key):
                return str(value[key])
    return None


def _amount_of(value: Any) -> tuple[float | None, str | None]:
    """Money can be a dict {amount,currency}, a number, or a formatted string."""
    if isinstance(value, dict):
        amt = value.get("amount")
        cur = value.get("currency")
        if amt is not None:
            num, sniffed = parse_money(amt)
            return num, cur or sniffed
        return None, cur
    return parse_money(value)


@dataclass
class Bidder:
    name: str = ""
    eik: str | None = None
    amount: float | None = None
    submitted_at: datetime | None = None
    disqualified: bool = False


@dataclass
class TenderView:
    source: str
    natural_key: str
    source_url: str
    payload: dict
    title: str = ""
    buyer_name: str = ""
    buyer_eik: str | None = None
    winner_name: str = ""
    winner_eik: str | None = None
    value_amount: float | None = None
    value_currency: str | None = None
    estimated_amount: float | None = None
    final_amount: float | None = None
    cpv: str | None = None
    procedure_type: str = ""
    status: str = ""
    published_at: datetime | None = None
    deadline: datetime | None = None
    award_date: datetime | None = None
    signed_at: datetime | None = None
    bids_count: int | None = None
    bidders: list[Bidder] = field(default_factory=list)
    subcontractors: list[str] = field(default_factory=list)
    full_text: str = ""
    corpus_index: int | None = None  # position in AnalysisContext.views (for similarity)

    @property
    def cpv_division(self) -> str | None:
        """First two CPV digits = the broad category (e.g. '45' = construction)."""
        return self.cpv[:2] if self.cpv else None


def _parse_bidders(payload: dict) -> list[Bidder]:
    raw = payload.get("bidders") or payload.get("offers") or payload.get("participants")
    if not isinstance(raw, list):
        return []
    out: list[Bidder] = []
    for item in raw:
        if not isinstance(item, dict):
            name = clean_text(item) if isinstance(item, str) else ""
            if name:
                out.append(Bidder(name=name))
            continue
        amount, _ = _amount_of(item.get("amount") or item.get("price") or item.get("value"))
        disq = bool(
            item.get("disqualified")
            or item.get("rejected")
            or (str(item.get("status", "")).lower() in {"rejected", "disqualified", "отстранен"})
        )
        out.append(
            Bidder(
                name=_name_of(item),
                eik=_eik_of(item),
                amount=amount,
                submitted_at=parse_date(item.get("submitted_at") or item.get("submitted")),
                disqualified=disq,
            )
        )
    return out


def _build_full_text(source: str, payload: dict) -> str:
    parts = [build_document(source, payload)]
    for key in _TEXT_KEYS:
        val = payload.get(key)
        if isinstance(val, str) and val.strip():
            parts.append(clean_text(val))
        elif isinstance(val, list):
            parts.extend(clean_text(v) for v in val if isinstance(v, str))
    # Dedupe while preserving order.
    seen: list[str] = []
    for p in parts:
        if p and p not in seen:
            seen.append(p)
    return "\n".join(seen)


def _flatten_payload(payload: dict) -> dict:
    """Surface the v2 typed block (``payload[record_type]``, e.g. ``tender``) up to the
    top level so the flat field readers below see ``value``/``cpv_code``/``bidders``.

    The v2 contract nests the typed fields under a block keyed by ``record_type``
    (contract.py / TenderPayloadMapper), while the header (title, authority, winner,
    sphere, category) stays at the top. Older flat payloads have everything at the top
    already, so this is a no-op for them. Header keys win on collision.
    """
    record_type = payload.get("record_type")
    block = payload.get(record_type) if isinstance(record_type, str) else None
    if not isinstance(block, dict):
        return payload
    return {**block, **payload}


def view_from_record(record: dict) -> TenderView:
    """Map one IngestRecord dict into a :class:`TenderView`."""
    source = record.get("source", "")
    payload = record.get("payload") or {}
    data = _flatten_payload(payload)

    value_amount, value_currency = _amount_of(_first(data, ("value", "amount", "price", "стойност")))
    estimated, _ = _amount_of(_first(data, ("estimated_value", "estimated", "прогнозна_стойност")))
    final, _ = _amount_of(_first(data, ("final_value", "final_amount", "paid", "contract_value")))

    bidders = _parse_bidders(data)
    bids_count = data.get("bids_count") or data.get("offers_count")
    if bids_count is None and bidders:
        bids_count = len(bidders)

    subs_raw = data.get("subcontractors") or []
    subs = [clean_text(s) for s in subs_raw if isinstance(s, str)] if isinstance(subs_raw, list) else []

    cpv_raw = data.get("cpv") or data.get("cpv_code")

    return TenderView(
        source=source,
        natural_key=record.get("natural_key", ""),
        source_url=record.get("source_url", ""),
        payload=payload,
        title=_name_of(_first(data, _FIRST_KEYS_TITLE)) or clean_text(data.get("title")),
        buyer_name=_name_of(_first(data, _FIRST_KEYS_BUYER)),
        buyer_eik=_eik_of(_first(data, _FIRST_KEYS_BUYER)),
        winner_name=_name_of(_first(data, _FIRST_KEYS_WINNER)),
        winner_eik=_eik_of(_first(data, _FIRST_KEYS_WINNER)),
        value_amount=value_amount,
        value_currency=value_currency,
        estimated_amount=estimated,
        final_amount=final,
        cpv=extract_cpv(str(cpv_raw)) if cpv_raw else None,
        procedure_type=clean_text(str(_first(data, _FIRST_KEYS_PROCEDURE) or "")),
        status=clean_text(str(_first(data, _FIRST_KEYS_STATUS) or "")),
        published_at=parse_date(data.get("published_at") or data.get("published") or data.get("announced_at")),
        deadline=parse_date(data.get("deadline") or data.get("deadline_at")),
        award_date=parse_date(data.get("award_date") or data.get("awarded_at")),
        signed_at=parse_date(data.get("contract_signed_at") or data.get("signed_at")),
        bids_count=int(bids_count) if isinstance(bids_count, (int, float)) else None,
        bidders=bidders,
        subcontractors=subs,
        full_text=_build_full_text(source, data),
    )
