from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mz_jobs import MzJobsSource
from scraper.spheres import CATEGORY_JOBS, SPHERE_HEALTHCARE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mz_jobs_parser():
    content = (FIXTURES / "mz_jobs_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")

    source = MzJobsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))

    assert len(records) == 2
    assert "УМБАЛ Св. Екатерина" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-01")
    assert source.sphere == SPHERE_HEALTHCARE
    assert source.category == CATEGORY_JOBS
