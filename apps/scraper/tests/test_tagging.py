from __future__ import annotations

from unittest.mock import MagicMock

from scraper.contract import IngestRecord
from scraper.sources.base import RawPayload, Source
from scraper.spheres import CATEGORY_PROCUREMENT, SPHERE_HEALTHCARE


class MockSource(Source):
    id = "mock"
    
    def fetch(self):
        yield RawPayload(source_url="http://test", content=b"data")
        
    def parse(self, payload):
        yield IngestRecord(
            source=self.id,
            natural_key="123",
            source_url=payload.source_url,
            fetched_at=payload.fetched_at,
            payload={"authority": {"name": "МБАЛ"}, "cpv": "33000000"}
        )


def test_automatic_tagging():
    sink = MagicMock()
    source = MockSource(MagicMock(), sink, MagicMock(), MagicMock())
    
    records = list(source.records())
    
    assert len(records) == 1
    assert records[0].payload["category"] == CATEGORY_PROCUREMENT
    assert records[0].payload["sphere"] == SPHERE_HEALTHCARE


def test_fixed_sphere_tagging():
    sink = MagicMock()
    source = MockSource(MagicMock(), sink, MagicMock(), MagicMock())
    source.sphere = "custom"
    
    records = list(source.records())
    
    assert records[0].payload["sphere"] == "custom"
