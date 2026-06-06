from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.vss_jobs import VssJobsSource
from scraper.spheres import CATEGORY_JOBS, SPHERE_JUDICIARY

FIXTURES = Path(__file__).parent / "fixtures"


def test_vss_jobs_parser():
    content = (FIXTURES / "vss_jobs_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")

    source = VssJobsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))

    assert len(records) == 2
    assert "младши съдии" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-01-30")
    assert records[0].natural_key == "112244"
    assert source.sphere == SPHERE_JUDICIARY
    assert source.category == CATEGORY_JOBS
