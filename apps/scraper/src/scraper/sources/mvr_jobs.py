"""MVR job competitions — detect nepotism and rigged hiring."""

from __future__ import annotations

from ..spheres import CATEGORY_JOBS, SPHERE_POLICE
from .listing import ListingSource


class MvrJobsSource(ListingSource):
    id = "mvr_jobs"
    sphere = SPHERE_POLICE
    category = CATEGORY_JOBS
    pages_env = "MVR_JOBS_PAGES"
    buyer = "МВР"
    item_tags = ("li", "div", "p")
    min_text_len = 15
