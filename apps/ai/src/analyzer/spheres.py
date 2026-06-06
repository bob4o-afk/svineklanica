"""Sphere and category constants for the AI analyzer.

Mirrors the scraper's taxonomy and defines per-sphere flow categories.
"""

from __future__ import annotations

# Spheres (сфери)
SPHERE_HEALTHCARE = "здравеопазване"
SPHERE_JUDICIARY = "съдебна система"
SPHERE_POLICE = "полиция"
SPHERE_EDUCATION = "образование"

# Scraper categories (категории)
CATEGORY_PROCUREMENT = "обществена поръчка"
CATEGORY_PAYMENTS = "нерегламентирани плащания"
CATEGORY_JOBS = "конкурси за работа"

# Flow category labels (AI routing targets)
CAT_DRUGS = "лекарства"
CAT_PROCUREMENT = "обществена поръчка"
CAT_JOBS = "конкурси за работа"
CAT_ASSETS = "продажба на активи"
CAT_DECLARATIONS = "нерегламентирани плащания"
CAT_DONATIONS = "дарения за МВР"

# Flow keys
FLOW_DRUGS = "drugs"
FLOW_PROCUREMENT = "procurement"
FLOW_JOBS = "jobs"
FLOW_ASSETS = "assets"
FLOW_DECLARATIONS = "declarations"
FLOW_DONATIONS = "donations"

HEALTHCARE_SOURCES = frozenset({"ncpr", "nhif", "mz", "mz_jobs", "mz_assets"})
JUDICIARY_SOURCES = frozenset({"vss", "prb", "vss_jobs", "ivss_declarations", "mjs_assets"})
POLICE_SOURCES = frozenset({"mvr", "mvr_donations", "mvr_jobs", "mvr_assets"})

SPHERE_SOURCE_MAP: dict[str, frozenset[str]] = {
    SPHERE_HEALTHCARE: HEALTHCARE_SOURCES,
    SPHERE_JUDICIARY: JUDICIARY_SOURCES,
    SPHERE_POLICE: POLICE_SOURCES,
}

CROSS_CUTTING_SOURCES = frozenset(
    {"ted", "caiseop", "eop", "aop", "egov", "isun", "sebra"}
)

# CLI sphere aliases -> sphere string
SPHERE_CLI_ALIASES: dict[str, str] = {
    "healthcare": SPHERE_HEALTHCARE,
    "judiciary": SPHERE_JUDICIARY,
    "police": SPHERE_POLICE,
}

def sources_for_sphere(sphere: str) -> frozenset[str]:
    return SPHERE_SOURCE_MAP.get(sphere, frozenset())
