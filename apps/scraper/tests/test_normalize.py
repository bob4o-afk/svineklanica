from datetime import timezone

import pytest

from scraper.normalize import (
    extract_cpv,
    is_valid_eik,
    normalize_company_name,
    parse_date,
    parse_money,
    to_utc_iso,
)


@pytest.mark.parametrize(
    "raw,amount,currency",
    [
        ("120 000,50 лв.", 120000.50, "BGN"),
        ("1.234,56", 1234.56, None),
        ("1234.56 EUR", 1234.56, "EUR"),
        ("12000", 12000.0, None),
        ("1 000 000 лева", 1000000.0, "BGN"),
        ("3,5", 3.5, None),
        ("", None, None),
        ("лв.", None, "BGN"),
    ],
)
def test_parse_money(raw, amount, currency):
    assert parse_money(raw) == (amount, currency)


def test_parse_money_numeric_passthrough():
    assert parse_money(42) == (42.0, None)
    assert parse_money(3.14) == (3.14, None)


def test_parse_date_dayfirst_utc():
    dt = parse_date("15.03.2024")
    assert dt is not None
    assert (dt.year, dt.month, dt.day) == (2024, 3, 15)
    assert dt.tzinfo == timezone.utc
    assert parse_date("not a date") is None
    assert parse_date("") is None


def test_to_utc_iso_naive_becomes_utc():
    dt = parse_date("01.01.2024")
    assert to_utc_iso(dt).endswith("+00:00")


def test_extract_cpv():
    assert extract_cpv("30213100-6 Преносими компютри") == "30213100"
    assert extract_cpv("CPV: 45233142") == "45233142"
    assert extract_cpv("няма код") is None


def test_normalize_company_name():
    assert normalize_company_name("  Техно   Трейд  ООД ") == "ТЕХНО ТРЕЙД ООД"
    assert normalize_company_name('„Пътстрой" АД') == "ПЪТСТРОЙ АД"
    assert normalize_company_name(None) == ""


@pytest.mark.parametrize("eik", ["100000019", "100000026", "100000001"])
def test_valid_eik(eik):
    assert is_valid_eik(eik) is True


@pytest.mark.parametrize("eik", ["999999999", "12345", "", None, "abcdefghi", "1000000190"])
def test_invalid_eik(eik):
    assert is_valid_eik(eik) is False


def test_eik_accepts_int_and_strips_noise():
    assert is_valid_eik(100000019) is True
    assert is_valid_eik("ЕИК 100000019") is True
