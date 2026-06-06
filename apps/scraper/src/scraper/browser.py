"""Optional Playwright helper for JS-rendered / WAF-gated sources.

Used only by sources that can't be fetched with plain ``httpx`` (app.eop.bg is
JS-heavy; 2020.eufunds.bg sits behind a WAF that 403s non-browser clients).
Playwright is an **optional** dependency (``uv sync --extra browser``); importing
this without it installed raises a clear, actionable error.

The SSRF allow-list still applies: callers must validate the host before
rendering (the source modules do).
"""

from __future__ import annotations

from dataclasses import dataclass


class PlaywrightNotInstalled(RuntimeError):
    pass


@dataclass
class RenderedPage:
    url: str
    html: str


def render(url: str, *, user_agent: str, timeout_s: float = 45.0,
           wait_selector: str | None = None) -> RenderedPage:
    """Render ``url`` in a headless Chromium and return the final HTML."""
    try:
        from playwright.sync_api import sync_playwright
    except ImportError as exc:  # pragma: no cover - depends on optional extra
        raise PlaywrightNotInstalled(
            "Playwright is required for this source. Install it with:\n"
            "  uv sync --extra browser && uv run playwright install chromium"
        ) from exc

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        try:
            page = browser.new_context(user_agent=user_agent).new_page()
            page.goto(url, timeout=timeout_s * 1000, wait_until="domcontentloaded")
            if wait_selector:
                page.wait_for_selector(wait_selector, timeout=timeout_s * 1000)
            html = page.content()
        finally:
            browser.close()
    return RenderedPage(url=url, html=html)
