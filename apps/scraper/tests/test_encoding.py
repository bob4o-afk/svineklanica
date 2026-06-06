from scraper.encoding import decode_bytes, has_cyrillic, looks_like_mojibake

BG = "Обществена поръчка: доставка на лаптопи (ще, ъ, я)"


def test_decode_utf8():
    assert decode_bytes(BG.encode("utf-8")) == BG


def test_decode_cp1251_legacy():
    raw = BG.encode("cp1251")
    decoded = decode_bytes(raw)
    assert decoded == BG
    assert has_cyrillic(decoded)


def test_decode_cp1251_with_hint():
    raw = BG.encode("cp1251")
    assert decode_bytes(raw, hint="windows-1251") == BG


def test_decode_empty():
    assert decode_bytes(b"") == ""


def test_decode_never_raises_on_garbage():
    # Random bytes must not blow up the run.
    out = decode_bytes(bytes(range(256)))
    assert isinstance(out, str)


def test_cyrillic_spot_check():
    assert has_cyrillic("ще ъ я") is True
    assert has_cyrillic("hello world") is False


def test_mojibake_detection():
    mojibake = "Обществена".encode("cp1251").decode("latin-1")
    assert looks_like_mojibake(mojibake) is True
    assert looks_like_mojibake("чисто UTF-8 текст") is False
