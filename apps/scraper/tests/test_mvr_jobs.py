from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.mvr_jobs import MvrJobsSource
from scraper.spheres import CATEGORY_JOBS, SPHERE_POLICE

FIXTURES = Path(__file__).parent / "fixtures"


def test_mvr_jobs_parser():
    content = (FIXTURES / "mvr_jobs_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")
    
    source = MvrJobsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))
    
    assert len(records) == 2
    assert "Конкурс за разследващ полицай" in records[0].payload["title"]
    assert records[0].payload["published_at"].startswith("2026-06-04")
    assert records[0].natural_key == "333"
    assert source.sphere == SPHERE_POLICE
    assert source.category == CATEGORY_JOBS
