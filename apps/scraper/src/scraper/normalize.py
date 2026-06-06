"""Shared normalization helpers used by every source.

Money, dates, CPV codes, company EIK (БУЛСТАТ) and free-text cleanup. The hard
part of procurement data lives here (``.claude/rules/data-sources.md`` §3):
prices must be comparable (currency + VAT), companies unify on EIK not name.

Bulgarian text is **kept Bulgarian** — we clean whitespace, never translate.
"""

from __future__ import annotations

import re
from datetime import datetime, timezone

from dateutil import parser as date_parser

_WS = re.compile(r"\s+")
_CPV = re.compile(r"\b(\d{8})(?:-\d)?\b")
_MONEY_CHARS = re.compile(r"[^\d,.\-]")

_CURRENCY_PATTERNS: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"\b(?:bgn|лв\.?|лева)\b", re.IGNORECASE), "BGN"),
    (re.compile(r"\b(?:eur|евро)\b|€", re.IGNORECASE), "EUR"),
    (re.compile(r"\b(?:usd)\b|\$", re.IGNORECASE), "USD"),
]


def clean_text(value: str | None) -> str:
    """Collapse whitespace and trim. Returns ``""`` for ``None``."""
    if not value:
        return ""
    return _WS.sub(" ", value).strip()


_TITLE_HINTS = ("предмет", "наименование", "описание", "subject", "title", "решение", "поръчка")
_AUTHORITY_HINTS = ("възложител", "authority", "купувач", "buyer", "институция", "ведомство", "орган")


def best_row_title(row: dict) -> str:
    """Best-effort title from a generic table row: a subject-ish column, else the
    longest text value. Lets a freeform register still yield a titled record."""
    for key, value in row.items():
        if isinstance(value, str) and value and any(h in str(key).lower() for h in _TITLE_HINTS):
            return clean_text(value)
    best = ""
    for value in row.values():
        if isinstance(value, str):
            cleaned = clean_text(value)
            if len(cleaned) > len(best):
                best = cleaned
    return best


def best_row_authority(row: dict) -> str | None:
    """Best-effort contracting-authority name from a generic table row, or None."""
    for key, value in row.items():
        if isinstance(value, str) and value and any(h in str(key).lower() for h in _AUTHORITY_HINTS):
            return clean_text(value)
    return None


def to_utc_iso(value: datetime) -> str:
    """Serialize a datetime to ISO-8601 in UTC (``...Z``-equivalent offset)."""
    if value.tzinfo is None:
        value = value.replace(tzinfo=timezone.utc)
    return value.astimezone(timezone.utc).isoformat()


def parse_date(value: str | None, *, dayfirst: bool = True) -> datetime | None:
    """Parse a Bulgarian/EU date string to a tz-aware UTC datetime, or ``None``.

    Bulgarian dates are day-first (``31.12.2024``), so that's the default.
    """
    text = clean_text(value)
    if not text:
        return None
    try:
        dt = date_parser.parse(text, dayfirst=dayfirst, fuzzy=True)
    except (ValueError, OverflowError, TypeError):
        return None
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    return dt.astimezone(timezone.utc)


def detect_currency(value: str) -> str | None:
    for pattern, code in _CURRENCY_PATTERNS:
        if pattern.search(value):
            return code
    return None


def parse_money(value: str | float | int | None) -> tuple[float | None, str | None]:
    """Parse a money string into ``(amount, currency)``.

    Handles BG/EU formats: ``1 234,56 лв.``, ``1.234,56``, ``1234.56 EUR``,
    ``12 000``. Currency is sniffed from the text (BGN/EUR/USD) when present.
    Returns ``(None, None)`` if no number can be read.
    """
    if value is None:
        return None, None
    if isinstance(value, (int, float)):
        return float(value), None

    text = str(value).strip()
    if not text:
        return None, None

    currency = detect_currency(text)
    digits = _MONEY_CHARS.sub("", text)
    if not digits or digits in {"-", ".", ","}:
        return None, currency

    amount = _parse_number(digits)
    return amount, currency


def _parse_number(digits: str) -> float | None:
    """Disambiguate ``,`` vs ``.`` as decimal/thousands separators."""
    # Trailing separators are junk (e.g. the dot in "лв." leaks through).
    digits = re.sub(r"[.,]+$", "", digits)
    digits = re.sub(r"^[.,]+", "", digits)
    if not digits or digits == "-":
        return None

    has_comma = "," in digits
    has_dot = "." in digits

    if has_comma and has_dot:
        # The right-most separator is the decimal one.
        if digits.rfind(",") > digits.rfind("."):
            digits = digits.replace(".", "").replace(",", ".")
        else:
            digits = digits.replace(",", "")
    elif has_comma:
        # A single comma with <=2 trailing digits is a decimal separator.
        if re.search(r",\d{1,2}$", digits):
            digits = digits.replace(",", ".")
        else:
            digits = digits.replace(",", "")
    # else: only dots / plain integer -> leave as-is

    try:
        return float(digits)
    except ValueError:
        return None


def extract_cpv(value: str | None) -> str | None:
    """Extract a Common Procurement Vocabulary code (8 digits) if present."""
    text = clean_text(value)
    if not text:
        return None
    match = _CPV.search(text)
    return match.group(1) if match else None


def normalize_company_name(name: str | None) -> str:
    """Upper-case, drop punctuation noise — for grouping/display, NOT identity.

    Company identity unifies on EIK; names are only a display/fallback key.
    """
    text = clean_text(name)
    if not text:
        return ""
    text = text.replace("„", '"').replace("“", '"').replace("”", '"')
    text = _WS.sub(" ", re.sub(r"[\"'`]+", "", text))
    return text.upper().strip()


def is_valid_eik(eik: str | int | None) -> bool:
    """Validate a Bulgarian EIK / БУЛСТАТ (9 or 13 digits) by its checksum.

    Implements the official ЕИК control-digit algorithm so we don't ingest a
    junk company id as a join key for the serial-winner detector.
    """
    if eik is None:
        return False
    digits = re.sub(r"\D", "", str(eik))
    if len(digits) not in (9, 13):
        return False

    nums = [int(c) for c in digits]
    check9 = _eik_check_9(nums)
    if check9 != nums[8]:
        return False
    if len(digits) == 9:
        return True
    return _eik_check_13(nums)


def _eik_check_9(nums: list[int]) -> int:
    weights1 = [1, 2, 3, 4, 5, 6, 7, 8]
    total = sum(n * w for n, w in zip(nums[:8], weights1))
    remainder = total % 11
    if remainder != 10:
        return remainder
    weights2 = [3, 4, 5, 6, 7, 8, 9, 10]
    total = sum(n * w for n, w in zip(nums[:8], weights2))
    remainder = total % 11
    return 0 if remainder == 10 else remainder


def _eik_check_13(nums: list[int]) -> bool:
    weights1 = [2, 7, 3, 5]
    weights2 = [4, 9, 5, 7]
    total = sum(n * w for n, w in zip(nums[8:12], weights1))
    remainder = total % 11
    if remainder == 10:
        total = sum(n * w for n, w in zip(nums[8:12], weights2))
        remainder = total % 11
        if remainder == 10:
            remainder = 0
    return remainder == nums[12]
