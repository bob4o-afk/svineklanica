from datetime import datetime, timezone

from scraper.contract import IngestRecord
from scraper.sinks import NdjsonSink

TS = datetime(2026, 6, 5, 12, 0, tzinfo=timezone.utc)


def _rec(key: str, title: str) -> IngestRecord:
    return IngestRecord(
        source="ted", natural_key=key, source_url="https://ted.europa.eu/x",
        fetched_at=TS, payload={"title": title},
    )


def test_write_dedupes_on_natural_key(make_config):
    sink = NdjsonSink(make_config(), "ted")
    result = sink.write([_rec("A", "first"), _rec("B", "second"), _rec("A", "updated")])
    assert result.written == 2
    assert result.duplicates == 1
    lines = result.normalized_path.read_text(encoding="utf-8").splitlines()
    assert len(lines) == 2
    # last write wins
    assert any('"updated"' in line for line in lines)


def test_write_is_idempotent_across_runs(make_config):
    cfg = make_config()
    records = [_rec("A", "x"), _rec("B", "y")]
    NdjsonSink(cfg, "ted").write(records)
    second = NdjsonSink(cfg, "ted").write(records)
    lines = second.normalized_path.read_text(encoding="utf-8").splitlines()
    assert len(lines) == 2  # no append/duplication


def test_normalized_output_is_utf8_cyrillic(make_config):
    sink = NdjsonSink(make_config(), "ted")
    result = sink.write([_rec("A", "Доставка на лаптопи")])
    text = result.normalized_path.read_text(encoding="utf-8")
    assert "Доставка на лаптопи" in text


def test_write_sample_limits(make_config):
    sink = NdjsonSink(make_config(), "ted")
    recs = [_rec(str(i), f"t{i}") for i in range(50)]
    path, n = sink.write_sample(recs, limit=10)
    assert n == 10
    assert len(path.read_text(encoding="utf-8").splitlines()) == 10


def test_save_raw_idempotent(make_config):
    sink = NdjsonSink(make_config(), "ted")
    p1 = sink.save_raw(b"<html>data</html>", ext="html")
    p2 = sink.save_raw(b"<html>data</html>", ext="html")
    assert p1 == p2
    assert p1.exists()
    assert p1.suffix == ".html"
