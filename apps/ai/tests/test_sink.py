from __future__ import annotations

from analyzer.schemas import VerdictRecord, utcnow
from analyzer.sinks import VerdictSink


def _verdict(key: str, score: float) -> VerdictRecord:
    return VerdictRecord(
        source="ted",
        natural_key=key,
        source_url=f"https://ted.europa.eu/notice/{key}",
        analyzed_at=utcnow(),
        corruption_score=score,
    )


def test_write_and_idempotent_upsert(tmp_path):
    sink = VerdictSink(tmp_path / "verdicts", tmp_path / "samples" / "verdicts")

    sink.write("ted", [_verdict("A", 10), _verdict("B", 20)])
    first = sink.read("ted")
    assert {v.natural_key for v in first} == {"A", "B"}

    # Re-running with an updated A and a new C: A replaced, B kept, C added.
    sink.write("ted", [_verdict("A", 99), _verdict("C", 30)])
    second = {v.natural_key: v.corruption_score for v in sink.read("ted")}
    assert second == {"A": 99, "B": 20, "C": 30}


def test_sample_slice_written(tmp_path):
    sink = VerdictSink(tmp_path / "verdicts", tmp_path / "samples" / "verdicts")
    result = sink.write("ted", [_verdict(str(i), i) for i in range(10)], sample_size=3)
    assert result.sample_path and result.sample_path.exists()
    sample_lines = result.sample_path.read_text(encoding="utf-8").strip().splitlines()
    assert len(sample_lines) == 3
