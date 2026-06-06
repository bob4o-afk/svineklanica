"""MZ / hospital asset disposal — detect undervalued sales of medical equipment or property."""

from __future__ import annotations

from ..spheres import CATEGORY_ASSETS, SPHERE_HEALTHCARE
from .listing import ListingSource


class MzAssetsSource(ListingSource):
    id = "mz_assets"
    sphere = SPHERE_HEALTHCARE
    category = CATEGORY_ASSETS
    pages_env = "MZ_ASSETS_PAGES"
    buyer = "Министерство на здравеопазването"
    item_tags = ("tr", "li", "div")
    min_text_len = 15
    extra_payload = {"type": "asset_disposal"}
