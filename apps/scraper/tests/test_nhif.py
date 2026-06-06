from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.nhif import NhifSource
from scraper.spheres import SPHERE_HEALTHCARE

FIXTURES = Path(__file__).parent / "fixtures"


def test_nhif_parser():
    content = (FIXTURES / "nhif_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = NhifSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert "Доставка на медицински консумативи" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-01")
    assert records[1].payload["published_at"].startswith("2026-05-15")
    assert source.sphere == SPHERE_HEALTHCARE
