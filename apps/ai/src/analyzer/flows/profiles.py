"""Per-sphere flow profiles: sources, routing maps, and flow registries."""

from __future__ import annotations

from dataclasses import dataclass

from ..agents import collusion, entity, lifecycle, scope, spec_rigging
from ..agents import (
    audit_findings,
    concession_abuse,
    conflict_kinship,
    donation_influence,
    drug_overpricing,
    gov_official_wealth,
    inn_steering,
    magistrate_competition,
    project_abuse,
    rigged_competition,
    undervalued_sale,
    unexplained_wealth,
)
from ..features import (
    amendments,
    assets,
    audits,
    collusion as collusion_feat,
    competition,
    concessions,
    declarations,
    donations,
    drug_pricing,
    entities,
    gov_declarations,
    jobs,
    lifecycle as lifecycle_feat,
    pricing,
    projects,
    thresholds,
    timing,
)
from ..spheres import (
    CAT_ASSETS,
    CAT_AUDITS,
    CAT_CONCESSIONS,
    CAT_DECLARATIONS,
    CAT_DONATIONS,
    CAT_DRUGS,
    CAT_GOV_DECLARATIONS,
    CAT_JOBS,
    CAT_PROCUREMENT,
    CAT_PROJECTS,
    CATEGORY_AUDITS,
    CATEGORY_CONCESSIONS,
    CATEGORY_DECLARATIONS,
    CATEGORY_JOBS,
    CATEGORY_PAYMENTS,
    CATEGORY_PROCUREMENT,
    CATEGORY_PROJECTS,
    FLOW_ASSETS,
    FLOW_AUDITS,
    FLOW_CONCESSIONS,
    FLOW_DECLARATIONS,
    FLOW_DONATIONS,
    FLOW_DRUGS,
    FLOW_GOV_DECLARATIONS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
    FLOW_PROJECTS,
    GOVERNMENT_SOURCES,
    HEALTHCARE_SOURCES,
    JUDICIARY_SOURCES,
    POLICE_SOURCES,
    ROADS_SOURCES,
    SPHERE_GOVERNMENT,
    SPHERE_HEALTHCARE,
    SPHERE_JUDICIARY,
    SPHERE_POLICE,
    SPHERE_ROADS,
)
from .base import Flow

_PROCUREMENT_FEATURES = (
    competition,
    timing,
    thresholds,
    pricing,
    amendments,
    collusion_feat,
    entities,
    lifecycle_feat,
)

_PROCUREMENT_AGENTS = (spec_rigging, scope, lifecycle, entity, collusion)

HEALTHCARE_FLOWS: dict[str, Flow] = {
    FLOW_DRUGS: Flow(
        key=FLOW_DRUGS,
        category=CAT_DRUGS,
        sphere=SPHERE_HEALTHCARE,
        agents=(drug_overpricing, inn_steering, scope, entity),
        feature_modules=(drug_pricing, pricing, competition, entities),
    ),
    FLOW_PROCUREMENT: Flow(
        key=FLOW_PROCUREMENT,
        category=CAT_PROCUREMENT,
        sphere=SPHERE_HEALTHCARE,
        agents=_PROCUREMENT_AGENTS,
        feature_modules=_PROCUREMENT_FEATURES,
    ),
    FLOW_JOBS: Flow(
        key=FLOW_JOBS,
        category=CAT_JOBS,
        sphere=SPHERE_HEALTHCARE,
        agents=(rigged_competition, conflict_kinship, entity),
        feature_modules=(jobs, entities, timing),
    ),
    FLOW_ASSETS: Flow(
        key=FLOW_ASSETS,
        category=CAT_ASSETS,
        sphere=SPHERE_HEALTHCARE,
        agents=(undervalued_sale, scope, entity),
        feature_modules=(assets, pricing, entities),
    ),
}

JUDICIARY_FLOWS: dict[str, Flow] = {
    FLOW_PROCUREMENT: Flow(
        key=FLOW_PROCUREMENT,
        category=CAT_PROCUREMENT,
        sphere=SPHERE_JUDICIARY,
        agents=_PROCUREMENT_AGENTS,
        feature_modules=_PROCUREMENT_FEATURES,
    ),
    FLOW_JOBS: Flow(
        key=FLOW_JOBS,
        category=CAT_JOBS,
        sphere=SPHERE_JUDICIARY,
        agents=(magistrate_competition, conflict_kinship, entity),
        feature_modules=(jobs, entities, timing),
    ),
    FLOW_DECLARATIONS: Flow(
        key=FLOW_DECLARATIONS,
        category=CAT_DECLARATIONS,
        sphere=SPHERE_JUDICIARY,
        agents=(unexplained_wealth, conflict_kinship),
        feature_modules=(declarations, entities),
    ),
    FLOW_ASSETS: Flow(
        key=FLOW_ASSETS,
        category=CAT_ASSETS,
        sphere=SPHERE_JUDICIARY,
        agents=(undervalued_sale, scope, entity),
        feature_modules=(assets, pricing, entities),
    ),
}


GOVERNMENT_FLOWS: dict[str, Flow] = {
    FLOW_PROCUREMENT: Flow(
        key=FLOW_PROCUREMENT,
        category=CAT_PROCUREMENT,
        sphere=SPHERE_GOVERNMENT,
        agents=_PROCUREMENT_AGENTS,
        feature_modules=_PROCUREMENT_FEATURES,
    ),
    FLOW_JOBS: Flow(
        key=FLOW_JOBS,
        category=CAT_JOBS,
        sphere=SPHERE_GOVERNMENT,
        agents=(rigged_competition, conflict_kinship, entity),
        feature_modules=(jobs, entities, timing),
    ),
    FLOW_AUDITS: Flow(
        key=FLOW_AUDITS,
        category=CAT_AUDITS,
        sphere=SPHERE_GOVERNMENT,
        agents=(audit_findings,),
        feature_modules=(audits,),
    ),
    FLOW_GOV_DECLARATIONS: Flow(
        key=FLOW_GOV_DECLARATIONS,
        category=CAT_GOV_DECLARATIONS,
        sphere=SPHERE_GOVERNMENT,
        agents=(gov_official_wealth, conflict_kinship),
        feature_modules=(gov_declarations, entities),
    ),
    FLOW_CONCESSIONS: Flow(
        key=FLOW_CONCESSIONS,
        category=CAT_CONCESSIONS,
        sphere=SPHERE_GOVERNMENT,
        agents=(concession_abuse, scope, lifecycle),
        feature_modules=(concessions, competition, pricing),
    ),
}

POLICE_FLOWS: dict[str, Flow] = {
    FLOW_PROCUREMENT: Flow(
        key=FLOW_PROCUREMENT,
        category=CAT_PROCUREMENT,
        sphere=SPHERE_POLICE,
        agents=_PROCUREMENT_AGENTS,
        feature_modules=_PROCUREMENT_FEATURES,
    ),
    FLOW_JOBS: Flow(
        key=FLOW_JOBS,
        category=CAT_JOBS,
        sphere=SPHERE_POLICE,
        agents=(rigged_competition, conflict_kinship, entity),
        feature_modules=(jobs, entities, timing),
    ),
    FLOW_ASSETS: Flow(
        key=FLOW_ASSETS,
        category=CAT_ASSETS,
        sphere=SPHERE_POLICE,
        agents=(undervalued_sale, scope, entity),
        feature_modules=(assets, pricing, entities),
    ),
    FLOW_DONATIONS: Flow(
        key=FLOW_DONATIONS,
        category=CAT_DONATIONS,
        sphere=SPHERE_POLICE,
        agents=(donation_influence, entity),
        feature_modules=(donations, entities),
    ),
}


@dataclass(frozen=True)
class SphereProfile:
    sphere: str
    sources: frozenset[str]
    source_to_flow: dict[str, str]
    payload_category_to_flow: dict[str, str]
    flows: dict[str, Flow]
    router_prompt: str
    valid_flows: frozenset[str]
    default_flow: str = FLOW_PROCUREMENT


HEALTHCARE_PROFILE = SphereProfile(
    sphere=SPHERE_HEALTHCARE,
    sources=HEALTHCARE_SOURCES,
    source_to_flow={
        "ncpr": FLOW_DRUGS,
        "nhif": FLOW_PROCUREMENT,
        "mz": FLOW_PROCUREMENT,
        "mz_jobs": FLOW_JOBS,
        "mz_assets": FLOW_ASSETS,
    },
    payload_category_to_flow={
        CAT_DRUGS: FLOW_DRUGS,
        CAT_PROCUREMENT: FLOW_PROCUREMENT,
        CAT_JOBS: FLOW_JOBS,
        CAT_ASSETS: FLOW_ASSETS,
        CATEGORY_JOBS: FLOW_JOBS,
    },
    flows=HEALTHCARE_FLOWS,
    router_prompt="category_router",
    valid_flows=frozenset({FLOW_DRUGS, FLOW_PROCUREMENT, FLOW_JOBS, FLOW_ASSETS}),
)

JUDICIARY_PROFILE = SphereProfile(
    sphere=SPHERE_JUDICIARY,
    sources=JUDICIARY_SOURCES,
    source_to_flow={
        "vss": FLOW_PROCUREMENT,
        "prb": FLOW_PROCUREMENT,
        "vss_jobs": FLOW_JOBS,
        "ivss_declarations": FLOW_DECLARATIONS,
        "mjs_assets": FLOW_ASSETS,
    },
    payload_category_to_flow={
        CAT_PROCUREMENT: FLOW_PROCUREMENT,
        CAT_JOBS: FLOW_JOBS,
        CAT_ASSETS: FLOW_ASSETS,
        CAT_DECLARATIONS: FLOW_DECLARATIONS,
        CATEGORY_PROCUREMENT: FLOW_PROCUREMENT,
        CATEGORY_JOBS: FLOW_JOBS,
        CATEGORY_PAYMENTS: FLOW_DECLARATIONS,
    },
    flows=JUDICIARY_FLOWS,
    router_prompt="judiciary_category_router",
    valid_flows=frozenset({FLOW_PROCUREMENT, FLOW_JOBS, FLOW_DECLARATIONS, FLOW_ASSETS}),
)

POLICE_PROFILE = SphereProfile(
    sphere=SPHERE_POLICE,
    sources=POLICE_SOURCES,
    source_to_flow={
        "mvr": FLOW_PROCUREMENT,
        "mvr_jobs": FLOW_JOBS,
        "mvr_assets": FLOW_ASSETS,
        "mvr_donations": FLOW_DONATIONS,
    },
    payload_category_to_flow={
        CAT_PROCUREMENT: FLOW_PROCUREMENT,
        CAT_JOBS: FLOW_JOBS,
        CAT_ASSETS: FLOW_ASSETS,
        CAT_DONATIONS: FLOW_DONATIONS,
        CATEGORY_PROCUREMENT: FLOW_PROCUREMENT,
        CATEGORY_JOBS: FLOW_JOBS,
        CATEGORY_PAYMENTS: FLOW_DONATIONS,
    },
    flows=POLICE_FLOWS,
    router_prompt="police_category_router",
    valid_flows=frozenset({FLOW_PROCUREMENT, FLOW_JOBS, FLOW_ASSETS, FLOW_DONATIONS}),
)

ROADS_FLOWS: dict[str, Flow] = {
    FLOW_PROCUREMENT: Flow(
        key=FLOW_PROCUREMENT,
        category=CAT_PROCUREMENT,
        sphere=SPHERE_ROADS,
        agents=_PROCUREMENT_AGENTS,
        feature_modules=_PROCUREMENT_FEATURES,
    ),
    FLOW_JOBS: Flow(
        key=FLOW_JOBS,
        category=CAT_JOBS,
        sphere=SPHERE_ROADS,
        agents=(rigged_competition, conflict_kinship, entity),
        feature_modules=(jobs, entities, timing),
    ),
    FLOW_PROJECTS: Flow(
        key=FLOW_PROJECTS,
        category=CAT_PROJECTS,
        sphere=SPHERE_ROADS,
        agents=(project_abuse, scope, lifecycle, entity),
        feature_modules=(projects, timing, entities),
    ),
}

GOVERNMENT_PROFILE = SphereProfile(
    sphere=SPHERE_GOVERNMENT,
    sources=GOVERNMENT_SOURCES,
    source_to_flow={
        "gov_tenders": FLOW_PROCUREMENT,
        "gov_jobs": FLOW_JOBS,
        "gov_audits": FLOW_AUDITS,
        "gov_declarations": FLOW_GOV_DECLARATIONS,
        "gov_concessions": FLOW_CONCESSIONS,
    },
    payload_category_to_flow={
        CAT_PROCUREMENT: FLOW_PROCUREMENT,
        CAT_JOBS: FLOW_JOBS,
        CAT_AUDITS: FLOW_AUDITS,
        CAT_GOV_DECLARATIONS: FLOW_GOV_DECLARATIONS,
        CAT_CONCESSIONS: FLOW_CONCESSIONS,
        CATEGORY_PROCUREMENT: FLOW_PROCUREMENT,
        CATEGORY_JOBS: FLOW_JOBS,
        CATEGORY_AUDITS: FLOW_AUDITS,
        CATEGORY_DECLARATIONS: FLOW_GOV_DECLARATIONS,
        CATEGORY_CONCESSIONS: FLOW_CONCESSIONS,
    },
    flows=GOVERNMENT_FLOWS,
    router_prompt="government_category_router",
    valid_flows=frozenset(
        {FLOW_PROCUREMENT, FLOW_JOBS, FLOW_AUDITS, FLOW_GOV_DECLARATIONS, FLOW_CONCESSIONS}
    ),
)

ROADS_PROFILE = SphereProfile(
    sphere=SPHERE_ROADS,
    sources=ROADS_SOURCES,
    source_to_flow={
        "api_tenders": FLOW_PROCUREMENT,
        "mrrb_tenders": FLOW_PROCUREMENT,
        "avtomagistrali_tenders": FLOW_PROCUREMENT,
        "api_jobs": FLOW_JOBS,
        "api_projects": FLOW_PROJECTS,
    },
    payload_category_to_flow={
        CAT_PROCUREMENT: FLOW_PROCUREMENT,
        CAT_JOBS: FLOW_JOBS,
        CAT_PROJECTS: FLOW_PROJECTS,
        CATEGORY_PROCUREMENT: FLOW_PROCUREMENT,
        CATEGORY_JOBS: FLOW_JOBS,
        CATEGORY_PROJECTS: FLOW_PROJECTS,
    },
    flows=ROADS_FLOWS,
    router_prompt="roads_category_router",
    valid_flows=frozenset({FLOW_PROCUREMENT, FLOW_JOBS, FLOW_PROJECTS}),
)

PROFILES: dict[str, SphereProfile] = {
    SPHERE_HEALTHCARE: HEALTHCARE_PROFILE,
    SPHERE_JUDICIARY: JUDICIARY_PROFILE,
    SPHERE_POLICE: POLICE_PROFILE,
    SPHERE_GOVERNMENT: GOVERNMENT_PROFILE,
    SPHERE_ROADS: ROADS_PROFILE,
}


def get_profile(sphere: str | None) -> SphereProfile:
    if sphere and sphere in PROFILES:
        return PROFILES[sphere]
    return HEALTHCARE_PROFILE
