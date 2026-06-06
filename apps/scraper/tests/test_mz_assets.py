from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mz_assets import MzAssetsSource
from scraper.spheres import SPHERE_HEALTHCARE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mz_assets_parser():
    content = (FIXTURES / "mz_assets_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")

    source = MzAssetsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))

    assert len(records) == 2
    assert "медицинска техника" in records[0].payload["title"]
    assert records[0].payload["type"] == "asset_disposal"
    assert records[0].natural_key == "301"
    assert source.sphere == SPHERE_HEALTHCARE
