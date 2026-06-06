"""MVR (МВР) procurement notices — police-specific tenders."""

from __future__ import annotations

from ..spheres import SPHERE_POLICE
from .listing import ListingSource


class MvrSource(ListingSource):
    id = "mvr"
    sphere = SPHERE_POLICE
    pages_env = "MVR_PAGES"
    buyer = "МВР"
    item_tags = ("tr", "li", "div")
    min_text_len = 10
