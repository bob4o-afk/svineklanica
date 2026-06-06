"""PRB (Прокуратура на РБ) procurement notices."""

from __future__ import annotations

from ..spheres import SPHERE_JUDICIARY
from .listing import ListingSource


class PrbSource(ListingSource):
    id = "prb"
    sphere = SPHERE_JUDICIARY
    pages_env = "PRB_PAGES"
    buyer = "Прокуратура на РБ"
    item_tags = ("li", "div", "h3")
    min_text_len = 10
