import time

import pytest

from scraper.http import DomainNotAllowed, PoliteClient


@pytest.fixture
def client(make_config):
    cfg = make_config({"ted": "https://ted.europa.eu", "egov": "https://data.egov.bg"})
    c = PoliteClient(cfg, respect_robots=False)
    yield c
    c.close()


def test_allows_listed_host(client):
    assert client._check_allowed("https://ted.europa.eu/en/notice/1") == "ted.europa.eu"


def test_allows_subdomain_of_listed_host(client):
    # TED's API lives on api.ted.europa.eu — a subdomain of the allow-listed root.
    assert client._check_allowed("https://api.ted.europa.eu/v3/notices/search") == \
        "api.ted.europa.eu"


def test_rejects_unlisted_host(client):
    with pytest.raises(DomainNotAllowed):
        client._check_allowed("https://evil.example.com/x")


def test_rejects_lookalike_suffix(client):
    # "notted.europa.eu" must NOT match "ted.europa.eu".
    with pytest.raises(DomainNotAllowed):
        client._check_allowed("https://notted.europa.eu/x")


def test_rejects_url_without_host(client):
    with pytest.raises(DomainNotAllowed):
        client._check_allowed("not-a-url")


def test_throttle_respects_rate_limit(make_config):
    cfg = make_config({"egov": "https://data.egov.bg"}, rate_limit_rps=20.0)
    client = PoliteClient(cfg, respect_robots=False)
    try:
        start = time.monotonic()
        client._throttle("data.egov.bg")
        client._throttle("data.egov.bg")
        elapsed = time.monotonic() - start
        assert elapsed >= 0.04  # ~1/20s between calls
    finally:
        client.close()
