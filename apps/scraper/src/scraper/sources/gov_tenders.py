"""Council of Ministers (Министерски съвет) procurement profile."""

from __future__ import annotations

from ..spheres import CATEGORY_PROCUREMENT, SPHERE_GOVERNMENT
from .listing import ListingSource


class GovTendersSource(ListingSource):
    id = "gov_tenders"
    sphere = SPHERE_GOVERNMENT
    category = CATEGORY_PROCUREMENT
    pages_env = "GOV_TENDERS_PAGES"
    buyer = "МИНИСТЕРСКИ СЪВЕТ"
    base_url = "https://www.government.bg"
    item_tags = ("li", "div")
    min_text_len = 20
