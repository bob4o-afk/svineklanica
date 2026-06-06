"""Polite, defensive HTTP client (``.claude/rules/scraping.md`` §6).

Guarantees, in order:
- **SSRF allow-list** — refuses any host that is not (a subdomain of) a
  configured source domain (``security.md`` §4). No arbitrary URLs, ever.
- **robots.txt** — honored per host (cached); a disallowed URL raises.
- **Throttle** — at most ``RATE_LIMIT_RPS`` requests/sec per host.
- **Retries** — exponential backoff on timeouts and 5xx / 429.
- **Cache** — fetched bytes are cached on disk so re-parsing never re-fetches.

We expose bad actors; we do not become one.
"""

from __future__ import annotations

import hashlib
import logging
import time
import urllib.robotparser
from dataclasses import dataclass, field
from pathlib import Path
from urllib.parse import urljoin, urlparse

import httpx

from .config import Config
from .encoding import decode_bytes

log = logging.getLogger("scraper.http")

RETRYABLE_STATUS = {429, 500, 502, 503, 504}


class DomainNotAllowed(Exception):
    """Raised when a fetch targets a host outside the SSRF allow-list."""


class RobotsDisallowed(Exception):
    """Raised when robots.txt forbids fetching a URL."""


@dataclass
class FetchResult:
    url: str
    status_code: int
    content: bytes
    headers: dict[str, str]
    from_cache: bool = False

    @property
    def charset(self) -> str | None:
        ctype = self.headers.get("content-type", "")
        if "charset=" in ctype:
            return ctype.split("charset=", 1)[1].split(";", 1)[0].strip()
        return None

    def text(self) -> str:
        return decode_bytes(self.content, hint=self.charset)


@dataclass
class PoliteClient:
    config: Config
    respect_robots: bool = True
    max_retries: int = 4
    use_cache: bool = True
    _client: httpx.Client = field(init=False)
    _last_request: dict[str, float] = field(default_factory=dict, init=False)
    _robots: dict[str, urllib.robotparser.RobotFileParser | None] = field(
        default_factory=dict, init=False
    )
    _allow: set[str] = field(default_factory=set, init=False)

    def __post_init__(self) -> None:
        self._allow = self.config.allow_listed_hosts()
        self._client = httpx.Client(
            headers={"User-Agent": self.config.user_agent},
            timeout=self.config.request_timeout_s,
            follow_redirects=True,
        )

    # -- context management ------------------------------------------------
    def __enter__(self) -> PoliteClient:
        return self

    def __exit__(self, *exc: object) -> None:
        self.close()

    def close(self) -> None:
        self._client.close()

    # -- guards ------------------------------------------------------------
    def _check_allowed(self, url: str) -> str:
        host = (urlparse(url).hostname or "").lower()
        if not host:
            raise DomainNotAllowed(f"URL has no host: {url!r}")
        if not self._host_allowed(host):
            allowed = ", ".join(sorted(self._allow)) or "(none)"
            raise DomainNotAllowed(
                f"Refusing to fetch '{host}' — not in SSRF allow-list ({allowed})."
            )
        return host

    def _host_allowed(self, host: str) -> bool:
        return any(host == a or host.endswith("." + a) for a in self._allow)

    def _check_robots(self, url: str, host: str) -> None:
        if not self.respect_robots:
            return
        parser = self._robots_for(url, host)
        if parser is None:
            return  # robots unreachable -> assume allowed, but logged
        if not parser.can_fetch(self.config.user_agent, url):
            raise RobotsDisallowed(f"robots.txt disallows {url}")

    def _robots_for(self, url: str, host: str) -> urllib.robotparser.RobotFileParser | None:
        if host in self._robots:
            return self._robots[host]
        robots_url = urljoin(f"{urlparse(url).scheme}://{host}", "/robots.txt")
        parser = urllib.robotparser.RobotFileParser()
        try:
            resp = self._client.get(robots_url)
            if resp.status_code >= 400:
                self._robots[host] = None
                return None
            parser.parse(resp.text.splitlines())
            self._robots[host] = parser
            return parser
        except httpx.HTTPError as exc:
            log.warning("robots.txt unreachable for %s: %s", host, exc)
            self._robots[host] = None
            return None

    def _throttle(self, host: str) -> None:
        rps = self.config.rate_limit_rps
        if rps <= 0:
            return
        min_interval = 1.0 / rps
        last = self._last_request.get(host)
        now = time.monotonic()
        if last is not None:
            wait = min_interval - (now - last)
            if wait > 0:
                time.sleep(wait)
        self._last_request[host] = time.monotonic()

    # -- cache -------------------------------------------------------------
    def _cache_path(self, url: str) -> Path:
        digest = hashlib.sha256(url.encode("utf-8")).hexdigest()[:32]
        return self.config.cache_dir / f"{digest}.bin"

    # -- fetch -------------------------------------------------------------
    def fetch(self, url: str, *, params: dict | None = None) -> FetchResult:
        """GET ``url`` (with guards, throttle, retry, cache)."""
        cache_key = url if not params else str(httpx.URL(url, params=params))
        return self._send("GET", url, params=params, json=None, cache_key=cache_key)

    def post(self, url: str, *, json: dict | None = None,
             params: dict | None = None) -> FetchResult:
        """POST ``url`` with a JSON body (same guards; cached by url+body)."""
        import json as _json

        body_sig = _json.dumps(json, sort_keys=True) if json is not None else ""
        cache_key = f"POST {url}?{params}#{body_sig}"
        return self._send("POST", url, params=params, json=json, cache_key=cache_key)

    def _send(self, method: str, url: str, *, params: dict | None,
              json: dict | None, cache_key: str) -> FetchResult:
        host = self._check_allowed(url)

        if self.use_cache:
            cached = self._read_cache(cache_key)
            if cached is not None:
                return cached

        self._check_robots(url, host)

        last_exc: Exception | None = None
        for attempt in range(self.max_retries):
            self._throttle(host)
            try:
                resp = self._client.request(method, url, params=params, json=json)
            except httpx.HTTPError as exc:
                last_exc = exc
                self._backoff(attempt)
                continue

            if resp.status_code in RETRYABLE_STATUS:
                last_exc = httpx.HTTPStatusError(
                    f"{resp.status_code}", request=resp.request, response=resp
                )
                self._backoff(attempt, resp)
                continue

            resp.raise_for_status()
            result = FetchResult(
                url=str(resp.url),
                status_code=resp.status_code,
                content=resp.content,
                headers={k.lower(): v for k, v in resp.headers.items()},
            )
            if self.use_cache:
                self._write_cache(cache_key, result.content)
            return result

        raise httpx.HTTPError(
            f"Failed to {method} {url} after {self.max_retries} attempts: {last_exc}"
        )

    def fetch_text(self, url: str, *, params: dict | None = None) -> str:
        return self.fetch(url, params=params).text()

    def fetch_json(self, url: str, *, params: dict | None = None):
        import json

        return json.loads(self.fetch_text(url, params=params))

    def _backoff(self, attempt: int, resp: httpx.Response | None = None) -> None:
        if resp is not None and (retry_after := resp.headers.get("retry-after")):
            try:
                time.sleep(min(float(retry_after), 30.0))
                return
            except ValueError:
                pass
        time.sleep(min(2.0**attempt, 30.0))

    def _read_cache(self, url: str) -> FetchResult | None:
        path = self._cache_path(url)
        if path.exists():
            return FetchResult(
                url=url,
                status_code=200,
                content=path.read_bytes(),
                headers={},
                from_cache=True,
            )
        return None

    def _write_cache(self, url: str, content: bytes) -> None:
        path = self._cache_path(url)
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(content)
