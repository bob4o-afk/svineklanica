"""Cyrillic-safe byte decoding (``.claude/rules/scraping.md`` §3).

Legacy Bulgarian government sites still serve ``windows-1251`` (``cp1251``);
modern open-data is UTF-8. We read raw bytes, try a hint, then UTF-8, then
``chardet`` detection, then fall back to ``cp1251`` so we never emit mojibake.
Everything downstream is UTF-8.
"""

from __future__ import annotations

import chardet

# Bulgarian Cyrillic block, used as a sanity check.
_CYRILLIC = set("абвгдежзийклмнопрстуфхцчшщъьюяАБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЬЮЯ")

# chardet sometimes reports these aliases for the same legacy encoding.
_LEGACY_ALIASES = {
    "windows-1251": "cp1251",
    "cp1251": "cp1251",
    "iso-8859-5": "iso-8859-5",
    "maccyrillic": "mac_cyrillic",
}


def decode_bytes(raw: bytes, hint: str | None = None) -> str:
    """Decode ``raw`` to ``str``, preferring UTF-8 and falling back to cp1251.

    ``hint`` is an optional encoding (e.g. from a ``charset`` header) that we try
    first. We never raise on bad bytes — the final fallback replaces them — so a
    single dirty record can't kill a whole run.
    """
    if not raw:
        return ""

    for enc in _candidate_order(raw, hint):
        try:
            return raw.decode(enc)
        except (UnicodeDecodeError, LookupError):
            continue

    # Last resort: cp1251 with replacement (legacy gov default).
    return raw.decode("cp1251", errors="replace")


def _candidate_order(raw: bytes, hint: str | None) -> list[str]:
    candidates: list[str] = []
    if hint:
        candidates.append(_LEGACY_ALIASES.get(hint.strip().lower(), hint.strip()))

    candidates.append("utf-8")

    detected = chardet.detect(raw).get("encoding")
    if detected:
        candidates.append(_LEGACY_ALIASES.get(detected.lower(), detected))

    candidates.append("cp1251")

    # De-duplicate, preserve order.
    seen: set[str] = set()
    ordered: list[str] = []
    for enc in candidates:
        key = enc.lower()
        if key not in seen:
            seen.add(key)
            ordered.append(enc)
    return ordered


def has_cyrillic(text: str) -> bool:
    """True if the string contains any Bulgarian Cyrillic letter."""
    return any(ch in _CYRILLIC for ch in text)


def looks_like_mojibake(text: str) -> bool:
    """Heuristic: Cyrillic mis-decoded as latin-1 yields a run of accented
    Latin-1 supplement characters (e.g. ``Обществена`` -> ``Îáùåñòâåíà``).

    Real Bulgarian UTF-8 lives in the Cyrillic block (U+0400+), so a high ratio
    of U+0080..U+00FF characters is a strong mojibake signal.
    """
    if not text:
        return False
    suspicious = sum(1 for ch in text if 0x80 <= ord(ch) <= 0x00FF)
    return suspicious > max(3, len(text) // 4)
