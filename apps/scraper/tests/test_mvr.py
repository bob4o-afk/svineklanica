from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mvr import MvrSource
from scraper.spheres import SPHERE_POLICE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mvr_parser():
    content = (FIXTURES / "mvr_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = MvrSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert "Доставка на патрулни автомобили" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-03")
    assert records[0].natural_key == "111"
    assert source.sphere == SPHERE_POLICE
