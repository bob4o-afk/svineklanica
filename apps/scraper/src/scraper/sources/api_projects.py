"""Road Infrastructure Agency (АПИ) major projects."""

from __future__ import annotations

from ..spheres import CATEGORY_PROJECTS, SPHERE_ROADS
from .listing import ListingSource


class ApiProjectsSource(ListingSource):
    id = "api_projects"
    sphere = SPHERE_ROADS
    category = CATEGORY_PROJECTS
    pages_env = "API_PROJECTS_PAGES"
    base_url = "https://www.api.bg"
    item_tags = ("li", "div", "tr", "h3")
    min_text_len = 30
