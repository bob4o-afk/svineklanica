"""Sphere and Category constants and classification logic."""

from __future__ import annotations

import re

# Spheres (сфери)
SPHERE_HEALTHCARE = "здравеопазване"
SPHERE_JUDICIARY = "съдебна система"
SPHERE_POLICE = "полиция"
SPHERE_GOVERNMENT = "правителство"
SPHERE_ROADS = "пътно строителство"
SPHERE_EDUCATION = "образование"

# Categories (категории)
CATEGORY_PROCUREMENT = "обществена поръчка"
CATEGORY_PAYMENTS = "нерегламентирани плащания"
CATEGORY_JOBS = "конкурси за работа"
CATEGORY_AUDITS = "одити"
CATEGORY_DECLARATIONS = "имуществени декларации"
CATEGORY_CONCESSIONS = "концесии"
CATEGORY_PROJECTS = "инфраструктурни проекти"

# Keywords for classification
_HEALTH_KEYWORDS = re.compile(
    r"\b(болница|мбал|умбал|дкц|нзок|рзок|здравноосигурителна|медицински|фарма|лекарства|пациент)\b",
    re.IGNORECASE,
)
_JUDICIARY_KEYWORDS = re.compile(
    r"\b(съд|прокуратура|всс|правосъдие|съдебна|магистрат|окръжен съд|районен съд|апелативен съд|върховен|следствена служба)\b",
    re.IGNORECASE,
)
_POLICE_KEYWORDS = re.compile(
    r"\b(мвр|полиция|жандармерия|гранична|полицейски|пожарна безопасност|одмвр|сдвр|гдбоп|вътрешни работи|борба с организираната престъпност)\b",
    re.IGNORECASE,
)
_GOVERNMENT_KEYWORDS = re.compile(
    r"\b(министерски съвет|сметна палата|кпконпи|цкпи|администрация|правителство|министерство|държавна агенция|национална агенция)\b",
    re.IGNORECASE,
)
_ROADS_KEYWORDS = re.compile(
    r"\b(апи|пътна инфраструктура|автомагистрали|пътно строителство|ремонт на пътища|асфалт|пътна мрежа|мррб)\b",
    re.IGNORECASE,
)


def classify_sphere(authority_name: str | None, cpv: str | None) -> str | None:
    """Infer the sphere from the contracting authority name or CPV code."""
    if not authority_name and not cpv:
        return None

    # 1. CPV based classification
    if cpv:
        if cpv.startswith("33") or cpv.startswith("85"):
            return SPHERE_HEALTHCARE
        if cpv.startswith("35"):
            return SPHERE_POLICE
        if cpv.startswith("45233"):
            return SPHERE_ROADS

    # 2. Authority name based classification
    if authority_name:
        name_low = authority_name.lower()
        if _HEALTH_KEYWORDS.search(name_low):
            return SPHERE_HEALTHCARE
        if _JUDICIARY_KEYWORDS.search(name_low):
            return SPHERE_JUDICIARY
        if _POLICE_KEYWORDS.search(name_low):
            return SPHERE_POLICE
        if _GOVERNMENT_KEYWORDS.search(name_low):
            return SPHERE_GOVERNMENT
        if _ROADS_KEYWORDS.search(name_low):
            return SPHERE_ROADS

    return None
