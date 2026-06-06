"""Road Infrastructure Agency (АПИ) procurement profile."""

from __future__ import annotations

from ..spheres import CATEGORY_PROCUREMENT, SPHERE_ROADS
from .listing import ListingSource


class ApiTendersSource(ListingSource):
    id = "api_tenders"
    sphere = SPHERE_ROADS
    category = CATEGORY_PROCUREMENT
    pages_env = "API_TENDERS_PAGES"
    buyer = "АГЕНЦИЯ ПЪТНА ИНФРАСТРУКТУРА"
    base_url = "https://www.api.bg"
    item_tags = ("li", "div", "tr")
    min_text_len = 20
