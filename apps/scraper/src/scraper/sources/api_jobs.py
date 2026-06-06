"""Road Infrastructure Agency (АПИ) job competitions."""

from __future__ import annotations

from ..spheres import CATEGORY_JOBS, SPHERE_ROADS
from .listing import ListingSource


class ApiJobsSource(ListingSource):
    id = "api_jobs"
    sphere = SPHERE_ROADS
    category = CATEGORY_JOBS
    pages_env = "API_JOBS_PAGES"
    base_url = "https://www.api.bg"
    item_tags = ("li", "div", "tr")
    min_text_len = 30
