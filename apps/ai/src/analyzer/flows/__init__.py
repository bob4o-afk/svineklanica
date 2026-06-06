"""Category flow registry (sphere-aware)."""

from __future__ import annotations

from .base import Flow, flow_category
from .profiles import HEALTHCARE_FLOWS, PROFILES, SphereProfile, get_profile

# Backward-compatible healthcare registry
FLOWS = HEALTHCARE_FLOWS
DEFAULT_FLOW = FLOWS["procurement"]


def get_flow(flow_key: str, sphere: str | None = None) -> Flow:
    """Return the Flow for a sphere + flow_key (falls back to healthcare procurement)."""
    profile = get_profile(sphere)
    return profile.flows.get(flow_key, profile.flows[profile.default_flow])


__all__ = ["FLOWS", "DEFAULT_FLOW", "PROFILES", "SphereProfile", "get_flow", "get_profile", "flow_category"]
