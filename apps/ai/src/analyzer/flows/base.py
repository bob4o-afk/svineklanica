"""Category flow definitions: agents + deterministic features per flow."""

from __future__ import annotations

from dataclasses import dataclass

from ..spheres import (
    CAT_ASSETS,
    CAT_AUDITS,
    CAT_CONCESSIONS,
    CAT_DECLARATIONS,
    CAT_DRUGS,
    CAT_GOV_DECLARATIONS,
    CAT_JOBS,
    CAT_PROCUREMENT,
    FLOW_ASSETS,
    FLOW_AUDITS,
    FLOW_CONCESSIONS,
    FLOW_DECLARATIONS,
    FLOW_DRUGS,
    FLOW_GOV_DECLARATIONS,
    FLOW_JOBS,
    FLOW_PROCUREMENT,
)


@dataclass(frozen=True)
class Flow:
    key: str
    category: str
    sphere: str
    agents: tuple
    feature_modules: tuple


def flow_category(flow_key: str) -> str:
    return {
        FLOW_DRUGS: CAT_DRUGS,
        FLOW_PROCUREMENT: CAT_PROCUREMENT,
        FLOW_JOBS: CAT_JOBS,
        FLOW_ASSETS: CAT_ASSETS,
        FLOW_DECLARATIONS: CAT_DECLARATIONS,
        FLOW_AUDITS: CAT_AUDITS,
        FLOW_GOV_DECLARATIONS: CAT_GOV_DECLARATIONS,
        FLOW_CONCESSIONS: CAT_CONCESSIONS,
    }.get(flow_key, CAT_PROCUREMENT)
