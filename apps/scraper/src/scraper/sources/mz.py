"""MZ (Министерство на здравеопазването) procurement notices."""

from __future__ import annotations

from ..spheres import SPHERE_HEALTHCARE
from .listing import ListingSource


class MzSource(ListingSource):
    id = "mz"
    sphere = SPHERE_HEALTHCARE
    pages_env = "MZ_PAGES"
    buyer = "Министерство на здравеопазването"
    item_tags = ("li", "div", "p", "tr")
    min_text_len = 10
