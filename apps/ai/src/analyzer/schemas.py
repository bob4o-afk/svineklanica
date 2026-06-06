"""Pydantic schemas: agent structured outputs, internal signals, and the verdict.

The agent ``*Output`` classes are the JSON schemas we hand to Gemini via
``with_structured_output``. The :class:`Flag` shape matches the backend's flag
schema (``.claude/rules/data-sources.md`` §4) 1:1, so ingest is trivial.
"""

from __future__ import annotations

from datetime import datetime, timezone
from typing import Any

from pydantic import BaseModel, ConfigDict, Field

SCHEMA_VERSION = 1


# --------------------------------------------------------------------------- #
# Corruption levels (Bulgarian, citizen-facing) + Flag taxonomy
# --------------------------------------------------------------------------- #

LEVEL_CORRUPTION = "Корупция"
LEVEL_HIGH = "Висок риск"
LEVEL_SUSPICIOUS = "Съмнително"
LEVEL_LOW = "Нисък риск"
LEVEL_NORMAL = "Нормално"

LEVELS = [LEVEL_NORMAL, LEVEL_LOW, LEVEL_SUSPICIOUS, LEVEL_HIGH, LEVEL_CORRUPTION]

SEVERITY_CRITICAL = "critical"
SEVERITY_HIGH = "high"
SEVERITY_MEDIUM = "medium"
SEVERITY_LOW = "low"

# Flag types (superset of CLAUDE.md §1.1 detectors).
FLAG_PRICE_DISCREPANCY = "price_discrepancy"
FLAG_TAILORED_SPEC = "tailored_spec"
FLAG_SERIAL_WINNER = "serial_winner"
FLAG_CANCELLED = "cancelled"
FLAG_IMPLAUSIBLE_SCOPE = "implausible_scope"
FLAG_DELAYED_PAYMENT = "delayed_payment"
FLAG_DOC_CLONE = "doc_clone"
FLAG_COLLUSION = "collusion"
FLAG_CONFLICT_OF_INTEREST = "conflict_of_interest"
FLAG_THRESHOLD_MANIPULATION = "threshold_manipulation"
FLAG_PROCEDURE_ABUSE = "procedure_abuse"
FLAG_AMENDMENT_ABUSE = "amendment_abuse"
FLAG_DRUG_OVERPRICING = "drug_overpricing"
FLAG_INN_STEERING = "inn_steering"
FLAG_RIGGED_COMPETITION = "rigged_competition"
FLAG_UNDERVALUED_SALE = "undervalued_sale"
FLAG_UNEXPLAINED_WEALTH = "unexplained_wealth"
FLAG_DONOR_INFLUENCE = "donor_influence"
FLAG_AUDIT_FINDING = "audit_finding"
FLAG_CONCESSION_ABUSE = "concession_abuse"
FLAG_PROJECT_ABUSE = "project_abuse"


# --------------------------------------------------------------------------- #
# Agent structured outputs (the JSON schemas Gemini fills)
# --------------------------------------------------------------------------- #

_Conf = Field(default=0.0, ge=0.0, le=1.0)


class SuspiciousCondition(BaseModel):
    quote: str = Field(description="The exact suspicious requirement, quoted from the tender text.")
    why_bg: str = Field(description="Why it restricts competition, in Bulgarian.")
    restrictiveness: float = Field(default=0.0, ge=0.0, le=1.0)


class SpecRiggingOutput(BaseModel):
    """Tailor-made / restrictive specifications (rigged tender)."""

    rigging_confidence: float = _Conf
    suspicious_conditions: list[SuspiciousCondition] = Field(default_factory=list)
    rationale_bg: str = ""


class ScopeRealismOutput(BaseModel):
    """Physically/financially implausible scope or overpricing narrative."""

    scope_implausibility: float = _Conf
    overpricing_suspicion: float = _Conf
    what_to_verify_bg: list[str] = Field(default_factory=list)
    rationale_bg: str = ""


class LifecycleOutput(BaseModel):
    """Cancel-after-award, re-tender with tweaked specs, amendment abuse."""

    cancellation_suspicion: float = _Conf
    amendment_abuse: float = _Conf
    reissue_link_bg: str = ""
    rationale_bg: str = ""


class EntityNetworkOutput(BaseModel):
    """Serial winner, shell clusters, kinship / conflict of interest."""

    serial_winner_suspicion: float = _Conf
    kinship_suspicion: float = _Conf
    conflict_of_interest: float = _Conf
    rationale_bg: str = ""


class CollusionOutput(BaseModel):
    """Bid rigging / cartel patterns across bidders."""

    collusion_confidence: float = _Conf
    pattern_type: str = Field(
        default="none",
        description="One of: cover_bidding, bid_suppression, bid_rotation, market_division, none.",
    )
    rationale_bg: str = ""


class AggregatorOutput(BaseModel):
    """Citizen-facing narrative (the model never sets the numeric score)."""

    headline_bg: str = ""
    explanation_bg: str = ""


class CategoryRouterOutput(BaseModel):
    """LLM fallback: pick the category flow for an ambiguous record."""

    flow_key: str = Field(
        default="procurement",
        description="Sphere-specific flow key (e.g. drugs, procurement, jobs, assets, declarations).",
    )
    confidence: float = _Conf
    rationale_bg: str = ""


class DrugOverpricingOutput(BaseModel):
    """Drug procurement overpricing vs NCPR ceiling / market benchmarks."""

    overpricing_confidence: float = _Conf
    markup_ratio: float | None = Field(default=None, description="Observed/ceiling ratio if known.")
    repackaging_suspicion: float = _Conf
    reimbursement_gaming: float = _Conf
    rationale_bg: str = ""


class INNSteeringOutput(BaseModel):
    """Brand/manufacturer steering instead of INN (eliminates generics)."""

    steering_confidence: float = _Conf
    brand_named: str = ""
    no_equivalent_clause: bool = False
    suspicious_conditions: list[SuspiciousCondition] = Field(default_factory=list)
    rationale_bg: str = ""


class RiggedCompetitionOutput(BaseModel):
    """Rigged job competition: short deadline, tailor-made eligibility."""

    rigging_confidence: float = _Conf
    short_deadline: bool = False
    hyper_specific_eligibility: bool = False
    single_eligible_candidate: bool = False
    holiday_timing: bool = False
    suspicious_conditions: list[SuspiciousCondition] = Field(default_factory=list)
    rationale_bg: str = ""


class ConflictKinshipOutput(BaseModel):
    """Kinship / conflict of interest in appointments or competitions."""

    kinship_confidence: float = _Conf
    conflict_confidence: float = _Conf
    named_parties: list[str] = Field(default_factory=list)
    rationale_bg: str = ""


class UndervaluedSaleOutput(BaseModel):
    """Undervalued asset sale / restrictive auction terms."""

    undervaluation_confidence: float = _Conf
    restrictive_terms: bool = False
    short_notice: bool = False
    insider_buyer_pattern: bool = False
    rationale_bg: str = ""


class MagistrateCompetitionOutput(BaseModel):
    """Rigged magistrate / judicial appointment competition."""

    rigging_confidence: float = _Conf
    rushed_procedure: bool = False
    atestation_manipulation: bool = False
    tailored_seniority: bool = False
    parachuting_candidate: bool = False
    single_eligible_candidate: bool = False
    holiday_timing: bool = False
    suspicious_conditions: list[SuspiciousCondition] = Field(default_factory=list)
    rationale_bg: str = ""


class UnexplainedWealthOutput(BaseModel):
    """Magistrate property declaration / unexplained wealth signals."""

    wealth_vs_income_suspicion: float = _Conf
    late_or_missing_declaration: bool = False
    sudden_increase: bool = False
    undeclared_interests: bool = False
    named_assets: list[str] = Field(default_factory=list)
    rationale_bg: str = ""


class DonationInfluenceOutput(BaseModel):
    """MVR donations register — undue influence / pay-to-play signals."""

    influence_suspicion: float = _Conf
    donor_is_regulated_or_supplier: bool = False
    in_kind_or_vehicle: bool = False
    repeat_donor: bool = False
    quid_pro_quo_suspicion: float = _Conf
    named_donor: str = ""
    rationale_bg: str = ""


class AuditFindingsOutput(BaseModel):
    """State Audit Office report — systemic misuse or repeat violations."""

    findings_severity: float = _Conf
    repeat_target: bool = False
    unimplemented_recommendations: bool = False
    named_institution: str = ""
    rationale_bg: str = ""


class GovOfficialWealthOutput(BaseModel):
    """High-level official property declaration (КПКОНПИ register)."""

    wealth_vs_income_suspicion: float = _Conf
    late_or_missing_declaration: bool = False
    sudden_increase: bool = False
    undeclared_interests: bool = False
    named_assets: list[str] = Field(default_factory=list)
    official_name: str = ""
    institution: str = ""
    rationale_bg: str = ""


class ConcessionAbuseOutput(BaseModel):
    """National Concession Register — pay-to-play or amendment abuse."""

    abuse_confidence: float = _Conf
    operator_lock_in: bool = False
    amendment_pattern: bool = False
    limited_competition: bool = False
    rationale_bg: str = ""


class ProjectAbuseOutput(BaseModel):
    """Road infrastructure project — delay, funding, or contractor lock-in abuse."""

    abuse_confidence: float = _Conf
    delay_pattern: bool = False
    funding_anomaly: bool = False
    contractor_lock_in: bool = False
    project_name: str = ""
    rationale_bg: str = ""


# --------------------------------------------------------------------------- #
# Internal scoring artifacts + the verdict written to the sidecar
# --------------------------------------------------------------------------- #


class Signal(BaseModel):
    """One extracted red-flag feature and its (auditable) score contribution."""

    key: str
    family: str
    code: str = ""  # catalog reference, e.g. "R018" (Open Contracting)
    risk: float = Field(ge=0.0, le=1.0)
    weight: float = 0.0
    contribution: float = 0.0
    value: Any = None
    source_field: str = ""
    rationale_bg: str = ""


class Flag(BaseModel):
    """Matches the backend Flag schema (data-sources.md §4)."""

    type: str
    severity: str
    subject: str
    source_urls: list[str] = Field(default_factory=list)
    explanation_bg: str = ""
    evidence: dict = Field(default_factory=dict)


class VerdictRecord(BaseModel):
    """One analyzed record -> written to storage/ingest/verdicts/<source>.ndjson."""

    model_config = ConfigDict(extra="forbid")

    source: str
    natural_key: str
    source_url: str
    analyzed_at: datetime
    schema_version: int = SCHEMA_VERSION
    model: str = ""
    corruption_score: float = Field(ge=0.0, le=100.0)
    level: str = LEVEL_NORMAL
    hard_tripped: bool = False
    signals: list[Signal] = Field(default_factory=list)
    flags: list[Flag] = Field(default_factory=list)
    agent_outputs: dict = Field(default_factory=dict)
    headline_bg: str = ""
    explanation_bg: str = ""
    sphere: str = ""
    category: str = ""
    flow_key: str = ""

    def to_ndjson_line(self) -> str:
        return self.model_dump_json()


def utcnow() -> datetime:
    return datetime.now(timezone.utc)
