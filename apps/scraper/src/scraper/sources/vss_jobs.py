"""VSS magistrate and admin competitions — detect rigged judicial appointments."""

from __future__ import annotations

from ..spheres import CATEGORY_JOBS, SPHERE_JUDICIARY
from .listing import ListingSource


class VssJobsSource(ListingSource):
    id = "vss_jobs"
    sphere = SPHERE_JUDICIARY
    category = CATEGORY_JOBS
    pages_env = "VSS_JOBS_PAGES"
    buyer = "Висш съдебен съвет"
    item_tags = ("li", "div", "p", "h3")
    min_text_len = 15
