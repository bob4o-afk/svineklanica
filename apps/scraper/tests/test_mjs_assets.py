from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mjs_assets import MjsAssetsSource
from scraper.spheres import SPHERE_JUDICIARY

FIXTURES = Path(__file__).parent / "fixtures"


def test_mjs_assets_parser():
    content = (FIXTURES / "mjs_assets_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")

    source = MjsAssetsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))

    assert len(records) == 2
    assert "районен съд" in records[0].payload["title"]
    assert records[0].payload["type"] == "asset_disposal"
    assert records[0].natural_key == "401"
    assert source.sphere == SPHERE_JUDICIARY
