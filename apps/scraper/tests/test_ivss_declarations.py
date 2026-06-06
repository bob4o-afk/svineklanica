from __future__ import annotations

from pathlib import Path
from unittest.mock import MagicMock

from scraper.sources.base import RawPayload
from scraper.sources.ivss_declarations import IvssDeclarationsSource
from scraper.spheres import CATEGORY_DECLARATIONS, SPHERE_JUDICIARY

FIXTURES = Path(__file__).parent / "fixtures"


def test_ivss_declarations_parser():
    content = (FIXTURES / "ivss_declarations_sample.html").read_bytes()
    payload = RawPayload(source_url="http://test.html", content=content, ext="html")

    source = IvssDeclarationsSource(MagicMock(), MagicMock(), MagicMock(), MagicMock())
    records = list(source.parse(payload))

    assert len(records) == 2
    assert records[0].payload["magistrate"] == "Иван Петров"
    assert records[0].payload["position"] == "Съдия"
    assert records[0].payload["court"] == "Окръжен съд София"
    assert records[0].payload["declared_at"].startswith("2026-01-01")
    assert records[0].payload["record_type"] == "declaration"
    assert source.sphere == SPHERE_JUDICIARY
    assert source.category == CATEGORY_DECLARATIONS
