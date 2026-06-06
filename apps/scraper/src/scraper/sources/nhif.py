"""NHIF (НЗОК) procurement notices — healthcare-specific tenders."""

from __future__ import annotations

from ..spheres import SPHERE_HEALTHCARE
from .listing import ListingSource


class NhifSource(ListingSource):
    id = "nhif"
    sphere = SPHERE_HEALTHCARE
    pages_env = "NHIF_PAGES"
    buyer = "НЗОК"
    item_tags = ("li", "p")
