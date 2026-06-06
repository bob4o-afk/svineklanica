from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mvr_assets import MvrAssetsSource
from scraper.spheres import CATEGORY_PROCUREMENT, SPHERE_POLICE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mvr_assets_parser():
    content = (FIXTURES / "mvr_assets_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = MvrAssetsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert "Търг за продажба на леки автомобили" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-05")
    assert records[0].natural_key == "555"
    assert source.sphere == SPHERE_POLICE
    assert source.category == CATEGORY_PROCUREMENT
