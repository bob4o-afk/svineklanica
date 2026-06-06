"""Deterministic + LLM routing to the correct category flow (sphere-aware)."""

from __future__ import annotations

from .agents.base import load_prompt, tender_brief
from .flows.profiles import SphereProfile, get_profile
from .llm import StructuredLLM
from .payload import TenderView
from .schemas import CategoryRouterOutput
from .spheres import (
    CROSS_CUTTING_SOURCES,
    FLOW_ASSETS,
    FLOW_DECLARATIONS,
    FLOW_DONATIONS,
    FLOW_DRUGS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
    HEALTHCARE_SOURCES,
    JUDICIARY_SOURCES,
    POLICE_SOURCES,
    SPHERE_HEALTHCARE,
    SPHERE_JUDICIARY,
    SPHERE_POLICE,
)


def resolve_sphere(view: TenderView) -> str | None:
    """Infer sphere from payload or source id."""
    payload_sphere = view.payload.get("sphere")
    if isinstance(payload_sphere, str) and payload_sphere.strip():
        return payload_sphere.strip()
    if view.source in HEALTHCARE_SOURCES:
        return SPHERE_HEALTHCARE
    if view.source in JUDICIARY_SOURCES:
        return SPHERE_JUDICIARY
    if view.source in POLICE_SOURCES:
        return SPHERE_POLICE
    return None


def _is_pharma_cpv(cpv: str | None) -> bool:
    return bool(cpv and cpv.startswith("33"))


def _is_healthcare_cpv(cpv: str | None) -> bool:
    return bool(cpv and (cpv.startswith("33") or cpv.startswith("85")))


def _is_judiciary_cpv(cpv: str | None) -> bool:
    return bool(cpv and cpv.startswith(("45", "71", "72", "79")))


def _is_asset_disposal(view: TenderView) -> bool:
    if view.payload.get("type") == "asset_disposal":
        return True
    blob = f"{view.title} {view.full_text}".lower()
    hints = ("продажба", "търг", "имот", "актив", "автомобил", "дма")
    return any(h in blob for h in hints)


def _is_job_competition(view: TenderView) -> bool:
    blob = f"{view.title} {view.full_text}".lower()
    hints = ("конкурс", "назначение", "директор", "магистрат", "съвет на директор", "младши съдия", "младши прокурор")
    return any(h in blob for h in hints)


def _is_declaration_record(view: TenderView) -> bool:
    if view.payload.get("magistrate"):
        return True
    blob = f"{view.title} {view.full_text}".lower()
    hints = ("декларация", "имотно състояние", "имуществ")
    return any(h in blob for h in hints)


def _is_donation_record(view: TenderView) -> bool:
    if view.payload.get("donor"):
        return True
    blob = f"{view.title} {view.full_text}".lower()
    hints = ("дарение", "дарител", "дарил", "donation")
    return any(h in blob for h in hints)


def _route_with_profile(view: TenderView, profile: SphereProfile, client: StructuredLLM | None) -> str:
    if view.source in profile.source_to_flow:
        return profile.source_to_flow[view.source]

    payload_cat = view.payload.get("category")
    if isinstance(payload_cat, str) and payload_cat in profile.payload_category_to_flow:
        mapped = profile.payload_category_to_flow[payload_cat]
        if mapped == FLOW_PROCUREMENT and _is_asset_disposal(view):
            return FLOW_ASSETS
        if mapped == FLOW_PROCUREMENT and _is_job_competition(view):
            return FLOW_JOBS
        if mapped == FLOW_PROCUREMENT and _is_declaration_record(view):
            return FLOW_DECLARATIONS
        if mapped == FLOW_PROCUREMENT and _is_donation_record(view) and FLOW_DONATIONS in profile.valid_flows:
            return FLOW_DONATIONS
        return mapped

    if view.source in CROSS_CUTTING_SOURCES:
        if profile.sphere == SPHERE_HEALTHCARE and _is_pharma_cpv(view.cpv):
            return FLOW_DRUGS
        if _is_declaration_record(view) and FLOW_DECLARATIONS in profile.valid_flows:
            return FLOW_DECLARATIONS
        if _is_donation_record(view) and FLOW_DONATIONS in profile.valid_flows:
            return FLOW_DONATIONS
        if _is_asset_disposal(view):
            return FLOW_ASSETS
        if _is_job_competition(view):
            return FLOW_JOBS
        if profile.sphere == SPHERE_HEALTHCARE and _is_healthcare_cpv(view.cpv):
            return FLOW_PROCUREMENT
        if profile.sphere == SPHERE_JUDICIARY and _is_judiciary_cpv(view.cpv):
            return FLOW_PROCUREMENT
        if client and client.available:
            return _llm_route(view, client, profile)
        return profile.default_flow

    if _is_declaration_record(view) and FLOW_DECLARATIONS in profile.valid_flows:
        return FLOW_DECLARATIONS
    if _is_donation_record(view) and FLOW_DONATIONS in profile.valid_flows:
        return FLOW_DONATIONS
    if _is_asset_disposal(view):
        return FLOW_ASSETS
    if _is_job_competition(view):
        return FLOW_JOBS
    if profile.sphere == SPHERE_HEALTHCARE and _is_pharma_cpv(view.cpv):
        return FLOW_DRUGS

    return profile.default_flow


def route_flow(
    view: TenderView,
    client: StructuredLLM | None = None,
    sphere: str | None = None,
) -> str:
    """Return a flow key for the resolved sphere profile."""
    resolved = sphere or resolve_sphere(view)
    profile = get_profile(resolved)
    return _route_with_profile(view, profile, client)


def _llm_route(view: TenderView, client: StructuredLLM, profile: SphereProfile) -> str:
    system = load_prompt(profile.router_prompt)
    valid = ", ".join(sorted(profile.valid_flows))
    user = (
        f"{tender_brief(view)}\n\n"
        f"Сфера: {profile.sphere}\n"
        f"Избери flow_key от: {valid}."
    )
    result = client.analyze(system, user, CategoryRouterOutput)
    if result is None:
        return profile.default_flow
    key = (result.flow_key or profile.default_flow).strip().lower()
    if key in profile.valid_flows:
        return key
    return profile.default_flow
