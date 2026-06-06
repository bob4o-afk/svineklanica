"""Sphere and category constants for the AI analyzer.

Mirrors the scraper's taxonomy and defines per-sphere flow categories.
"""

from __future__ import annotations

# Spheres (сфери)
SPHERE_HEALTHCARE = "здравеопазване"
SPHERE_JUDICIARY = "съдебна система"
SPHERE_POLICE = "полиция"
SPHERE_GOVERNMENT = "правителство"
SPHERE_ROADS = "пътно строителство"
SPHERE_EDUCATION = "образование"

# Scraper categories (категории)
CATEGORY_PROCUREMENT = "обществена поръчка"
CATEGORY_PAYMENTS = "нерегламентирани плащания"
CATEGORY_JOBS = "конкурси за работа"
CATEGORY_AUDITS = "одити"
CATEGORY_DECLARATIONS = "имуществени декларации"
CATEGORY_CONCESSIONS = "концесии"
CATEGORY_PROJECTS = "инфраструктурни проекти"

# Flow category labels (AI routing targets)
CAT_DRUGS = "лекарства"
CAT_PROCUREMENT = "обществена поръчка"
CAT_JOBS = "конкурси за работа"
CAT_ASSETS = "продажба на активи"
CAT_DECLARATIONS = "нерегламентирани плащания"
CAT_DONATIONS = "дарения за МВР"
CAT_AUDITS = "одити"
CAT_GOV_DECLARATIONS = "имуществени декларации"
CAT_CONCESSIONS = "концесии"
CAT_PROJECTS = "инфраструктурни проекти"

# Flow keys
FLOW_DRUGS = "drugs"
FLOW_PROCUREMENT = "procurement"
FLOW_JOBS = "jobs"
FLOW_ASSETS = "assets"
FLOW_DECLARATIONS = "declarations"
FLOW_DONATIONS = "donations"
FLOW_AUDITS = "audits"
FLOW_GOV_DECLARATIONS = "gov_declarations"
FLOW_CONCESSIONS = "concessions"
FLOW_PROJECTS = "projects"

HEALTHCARE_SOURCES = frozenset({"ncpr", "nhif", "mz", "mz_jobs", "mz_assets"})
JUDICIARY_SOURCES = frozenset({"vss", "prb", "vss_jobs", "ivss_declarations", "mjs_assets"})
POLICE_SOURCES = frozenset({"mvr", "mvr_donations", "mvr_jobs", "mvr_assets"})
GOVERNMENT_SOURCES = frozenset({"gov_tenders", "gov_jobs", "gov_audits", "gov_declarations", "gov_concessions"})
ROADS_SOURCES = frozenset({"api_tenders", "api_jobs", "api_projects", "mrrb_tenders", "avtomagistrali_tenders"})

SPHERE_SOURCE_MAP: dict[str, frozenset[str]] = {
    SPHERE_HEALTHCARE: HEALTHCARE_SOURCES,
    SPHERE_JUDICIARY: JUDICIARY_SOURCES,
    SPHERE_POLICE: POLICE_SOURCES,
    SPHERE_GOVERNMENT: GOVERNMENT_SOURCES,
    SPHERE_ROADS: ROADS_SOURCES,
}

CROSS_CUTTING_SOURCES = frozenset(
    {"ted", "caiseop", "eop", "aop", "egov", "isun", "sebra"}
)

# CLI sphere aliases -> sphere string
SPHERE_CLI_ALIASES: dict[str, str] = {
    "healthcare": SPHERE_HEALTHCARE,
    "judiciary": SPHERE_JUDICIARY,
    "police": SPHERE_POLICE,
    "government": SPHERE_GOVERNMENT,
    "roads": SPHERE_ROADS,
}

def sources_for_sphere(sphere: str) -> frozenset[str]:
    return SPHERE_SOURCE_MAP.get(sphere, frozenset())
