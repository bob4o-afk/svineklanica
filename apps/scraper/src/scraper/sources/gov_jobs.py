"""Central administration job competitions (ИИСДА)."""

from __future__ import annotations

from ..spheres import CATEGORY_JOBS, SPHERE_GOVERNMENT
from .listing import ListingSource


class GovJobsSource(ListingSource):
    id = "gov_jobs"
    sphere = SPHERE_GOVERNMENT
    category = CATEGORY_JOBS
    pages_env = "GOV_JOBS_PAGES"
    base_url = "https://iisda.government.bg"
    item_tags = ("tr", "div", "li")
    min_text_len = 30
