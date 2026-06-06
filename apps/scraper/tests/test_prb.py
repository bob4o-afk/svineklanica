from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.prb import PrbSource
from scraper.spheres import SPHERE_JUDICIARY

FIXTURES = Path(__file__).parent / "fixtures"


def test_prb_parser():
    content = (FIXTURES / "prb_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = PrbSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert "Доставка на гориво за прокуратурата" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-02")
    assert records[0].natural_key == "789"
    assert source.sphere == SPHERE_JUDICIARY
