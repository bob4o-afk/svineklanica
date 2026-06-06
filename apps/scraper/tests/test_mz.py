from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mz import MzSource
from scraper.spheres import SPHERE_HEALTHCARE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mz_parser():
    content = (FIXTURES / "mz_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")

    source = MzSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))

    assert len(records) == 2
    assert "Доставка на медицинска апаратура" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-03")
    assert records[0].natural_key == "101"
    assert source.sphere == SPHERE_HEALTHCARE
