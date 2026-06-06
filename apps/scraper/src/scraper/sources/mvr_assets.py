"""MVR asset disposal — detect sales of state property at undervalued prices."""

from __future__ import annotations

from ..spheres import SPHERE_POLICE
from .listing import ListingSource


class MvrAssetsSource(ListingSource):
    id = "mvr_assets"
    sphere = SPHERE_POLICE
    pages_env = "MVR_ASSETS_PAGES"
    buyer = "МВР"
    item_tags = ("tr", "li", "div")
    min_text_len = 15
    extra_payload = {"type": "asset_disposal"}
