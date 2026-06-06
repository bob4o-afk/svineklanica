"""MZ hospital director competitions — detect rigged appointments in public hospitals."""

from __future__ import annotations

from ..spheres import CATEGORY_JOBS, SPHERE_HEALTHCARE
from .listing import ListingSource


class MzJobsSource(ListingSource):
    id = "mz_jobs"
    sphere = SPHERE_HEALTHCARE
    category = CATEGORY_JOBS
    pages_env = "MZ_JOBS_PAGES"
    buyer = "Министерство на здравеопазването"
    item_tags = ("li", "div", "p", "h3")
    min_text_len = 15
