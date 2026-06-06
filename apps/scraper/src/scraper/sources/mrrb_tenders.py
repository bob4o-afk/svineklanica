"""Ministry of Regional Development (МРРБ) procurement profile."""

from __future__ import annotations

from ..spheres import CATEGORY_PROCUREMENT, SPHERE_ROADS
from .listing import ListingSource


class MrrbTendersSource(ListingSource):
    id = "mrrb_tenders"
    sphere = SPHERE_ROADS
    category = CATEGORY_PROCUREMENT
    pages_env = "MRRB_TENDERS_PAGES"
    buyer = "МИНИСТЕРСТВО НА РЕГИОНАЛНОТО РАЗВИТИЕ И БЛАГОУСТРОЙСТВОТО"
    base_url = "https://www.mrrb.bg"
    item_tags = ("li", "div", "tr")
    min_text_len = 20
