"""Avtomagistrali EAD procurement profile."""

from __future__ import annotations

from ..spheres import CATEGORY_PROCUREMENT, SPHERE_ROADS
from .listing import ListingSource


class AvtomagistraliTendersSource(ListingSource):
    id = "avtomagistrali_tenders"
    sphere = SPHERE_ROADS
    category = CATEGORY_PROCUREMENT
    pages_env = "AVTOMAGISTRALI_TENDERS_PAGES"
    buyer = "АВТОМАГИСТРАЛИ ЕАД"
    base_url = "https://avtomagistrali.com"
    item_tags = ("li", "div", "tr")
    min_text_len = 20
