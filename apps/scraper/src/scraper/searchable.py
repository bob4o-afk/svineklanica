"""Compose the *searchable document* for a record — the text we embed.

We deliberately do **not** embed just the title (too thin) nor the whole payload
(ids/urls/numbers add noise that hurts retrieval). Instead we build a curated
Bulgarian document per source: the subject/title plus the key entity names
(authority, winner, beneficiary) and the CPV, which is what a citizen actually
searches by. Bulgarian text stays Bulgarian.
"""

from __future__ import annotations

from .normalize import clean_text


def build_document(source: str, payload: dict) -> str:
    """Return the text to embed for one record's ``payload``."""
    builder = _BUILDERS.get(source, _generic)
    parts = builder(payload)
    text = " — ".join(p for p in (clean_text(p) for p in parts) if p)
    return text or _generic_text(payload)


def _ted(payload: dict) -> list[str]:
    return [payload.get("title", ""), payload.get("buyer", ""), _cpv(payload.get("cpv"))]


def _caiseop(payload: dict) -> list[str]:
    authority = (payload.get("authority") or {}).get("name", "")
    winner = (payload.get("winner") or {}).get("name", "")
    return [payload.get("subject", ""), authority, winner, _cpv(payload.get("cpv"))]


def _sebra(payload: dict) -> list[str]:
    return [payload.get("purpose", ""), payload.get("spender", ""), payload.get("recipient", "")]


def _isun(payload: dict) -> list[str]:
    fields = payload.get("fields") or []
    return [payload.get("beneficiary", ""), *fields]


def _eop(payload: dict) -> list[str]:
    return [payload.get("text", ""), *(payload.get("fields") or [])]


def _aop(payload: dict) -> list[str]:
    row = payload.get("row") or {}
    return list(row.values())


def _egov(payload: dict) -> list[str]:
    row = payload.get("row") or {}
    return [str(v) for v in row.values()]


def _generic(payload: dict) -> list[str]:
    return [_generic_text(payload)]


def _generic_text(payload: dict) -> str:
    """Fallback: gather every string leaf value (deduped, order-preserved)."""
    seen: list[str] = []
    _collect_strings(payload, seen)
    out: list[str] = []
    for s in seen:
        c = clean_text(s)
        if c and c not in out and not c.startswith("http"):
            out.append(c)
    return " — ".join(out)


def _collect_strings(value: object, acc: list[str]) -> None:
    if isinstance(value, str):
        acc.append(value)
    elif isinstance(value, dict):
        for v in value.values():
            _collect_strings(v, acc)
    elif isinstance(value, list):
        for v in value:
            _collect_strings(v, acc)


def _cpv(value: object) -> str:
    return f"CPV {value}" if value else ""


_BUILDERS = {
    "ted": _ted,
    "caiseop": _caiseop,
    "sebra": _sebra,
    "isun": _isun,
    "eop": _eop,
    "aop": _aop,
    "egov": _egov,
}
