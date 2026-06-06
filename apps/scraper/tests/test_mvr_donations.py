from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mvr_donations import MvrDonationsSource
from scraper.spheres import CATEGORY_PAYMENTS, SPHERE_POLICE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mvr_donations_parser():
    content = (FIXTURES / "mvr_donations_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = MvrDonationsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert records[0].payload["donor"] == "ФИРМА Х ЕООД"
    assert records[0].payload["value"]["amount"] == 1500.0
    assert records[0].payload["donated_at"].startswith("2026-06-01")
    assert source.sphere == SPHERE_POLICE
    assert source.category == CATEGORY_PAYMENTS
