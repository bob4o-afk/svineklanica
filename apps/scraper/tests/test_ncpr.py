from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.ncpr import NcprSource
from scraper.spheres import SPHERE_HEALTHCARE

FIXTURES = Path(__file__).parent / "fixtures"


def test_ncpr_parser():
    content = (FIXTURES / "ncpr_sample.csv").read_bytes()
    payload = RawPayload(source_url="http://test.csv", content=content, ext="csv")
    
    source = NcprSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert records[0].payload["product"] == "Парацетамол Таблетки"
    assert records[0].payload["price_ceiling"]["amount"] == 5.5
    assert records[0].payload["holder"] == "ФАРМА АД"
    assert records[1].payload["product"] == "Ибупрофен Сироп"
    assert records[1].payload["price_ceiling"]["amount"] == 8.2
    assert source.sphere == SPHERE_HEALTHCARE
