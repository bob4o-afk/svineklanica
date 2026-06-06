"""Ministry of Justice asset sales — detect undervalued disposal of court buildings."""

from __future__ import annotations

from ..spheres import SPHERE_JUDICIARY
from .listing import ListingSource


class MjsAssetsSource(ListingSource):
    id = "mjs_assets"
    sphere = SPHERE_JUDICIARY
    pages_env = "MJS_ASSETS_PAGES"
    buyer = "Министерство на правосъдието"
    item_tags = ("tr", "li", "div")
    min_text_len = 15
    extra_payload = {"type": "asset_disposal"}
