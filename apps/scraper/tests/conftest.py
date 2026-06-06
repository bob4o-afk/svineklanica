"""Pytest config: offline by default, opt-in live network tests.

Tests run with **no network** — source parsers are exercised against committed
fixtures. Tests marked ``@pytest.mark.network`` only run when ``--run-network``
is passed, so CI stays deterministic and we never hammer upstreams.
"""

from __future__ import annotations

from datetime import datetime, timezone
from pathlib import Path

import pytest

from scraper.config import Config, SourceConfig
from scraper.sources.base import RawPayload

FIXTURES = Path(__file__).parent / "fixtures"
FIXED_TS = datetime(2026, 6, 5, 12, 0, 0, tzinfo=timezone.utc)


def pytest_addoption(parser: pytest.Parser) -> None:
    parser.addoption("--run-network", action="store_true", default=False,
                     help="run tests that hit live upstream sources")


def pytest_collection_modifyitems(config: pytest.Config, items: list[pytest.Item]) -> None:
    if config.getoption("--run-network"):
        return
    skip = pytest.mark.skip(reason="needs --run-network")
    for item in items:
        if "network" in item.keywords:
            item.add_marker(skip)


@pytest.fixture
def fixtures_dir() -> Path:
    return FIXTURES


def read_fixture(name: str) -> bytes:
    return (FIXTURES / name).read_bytes()


@pytest.fixture
def make_config(tmp_path: Path):
    def _make(sources: dict[str, str] | None = None, **kwargs) -> Config:
        src = sources or {"egov": "https://data.egov.bg", "ted": "https://ted.europa.eu"}
        source_cfgs = {
            sid: SourceConfig(id=sid, enabled=True, base_url=url)
            for sid, url in src.items()
        }
        return Config(
            ingest_out_dir=tmp_path / "ingest",
            user_agent="test-agent/1.0",
            request_timeout_s=5.0,
            rate_limit_rps=kwargs.get("rate_limit_rps", 1000.0),
            sources=source_cfgs,
        )

    return _make


@pytest.fixture
def make_source(make_config):
    """Build a source instance suitable for offline ``parse`` testing.

    ``client``/``sink`` are ``None`` because ``parse`` never touches the network
    or disk — only ``records`` (the fetch+sink glue) does.
    """

    def _make(source_class, base_url: str | None = None):
        sid = source_class.id
        url = base_url or "https://example.bg"
        config = make_config({sid: url})
        return source_class(None, None, config.source(sid), config)

    return _make


@pytest.fixture
def payload_factory():
    def _make(name: str, source_url: str, ext: str = "bin", meta: dict | None = None):
        return RawPayload(
            source_url=source_url,
            content=read_fixture(name),
            ext=ext,
            fetched_at=FIXED_TS,
            meta=meta or {},
        )

    return _make
