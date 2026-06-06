"""National Concession Register (НКР) procedures."""

from __future__ import annotations

from ..spheres import CATEGORY_CONCESSIONS, SPHERE_GOVERNMENT
from .listing import ListingSource


class GovConcessionsSource(ListingSource):
    id = "gov_concessions"
    sphere = SPHERE_GOVERNMENT
    category = CATEGORY_CONCESSIONS
    pages_env = "GOV_CONCESSIONS_PAGES"
    base_url = "https://nkr.government.bg"
    item_tags = ("tr", "div", "li")
    min_text_len = 30
