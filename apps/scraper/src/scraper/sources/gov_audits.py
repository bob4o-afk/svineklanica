"""State Audit Office (Сметна палата) audit reports."""

from __future__ import annotations

from ..spheres import CATEGORY_AUDITS, SPHERE_GOVERNMENT
from .listing import ListingSource


class GovAuditsSource(ListingSource):
    id = "gov_audits"
    sphere = SPHERE_GOVERNMENT
    category = CATEGORY_AUDITS
    pages_env = "GOV_AUDITS_PAGES"
    base_url = "https://www.bulnao.government.bg"
    item_tags = ("tr", "div", "li")
    min_text_len = 30
