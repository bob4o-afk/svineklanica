from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.vss import VssSource
from scraper.spheres import SPHERE_JUDICIARY

FIXTURES = Path(__file__).parent / "fixtures"


def test_vss_parser():
    content = (FIXTURES / "vss_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = VssSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert records[0].payload["row"]["Предмет"] == "Ремонт на съдебна палата"
    assert records[0].payload["buyer"] == "ВСС"
    assert records[0].natural_key == "123"
    assert source.sphere == SPHERE_JUDICIARY
