"""The math: fuse signals into an auditable 0-100 corruption score + level.

Two stages:
  1. **Hard-trip rules** — a small set of strong, *sourced* combinations that go
     straight to 99/100 (kept conservative: a false 100 is disqualifying).
  2. **Composite score** — per-family noisy-OR of signal risks, weighted, summed,
     and passed through a normalized logistic (CRI-style: a composite beats any
     single indicator). Every signal's weight + contribution is recorded so the
     number is fully auditable.
"""

from __future__ import annotations

import json
import math
from collections import defaultdict
from pathlib import Path

from .payload import TenderView
from .schemas import (
    FLAG_AMENDMENT_ABUSE,
    FLAG_CANCELLED,
    FLAG_COLLUSION,
    FLAG_CONFLICT_OF_INTEREST,
    FLAG_DOC_CLONE,
    FLAG_DONOR_INFLUENCE,
    FLAG_DRUG_OVERPRICING,
    FLAG_IMPLAUSIBLE_SCOPE,
    FLAG_INN_STEERING,
    FLAG_PRICE_DISCREPANCY,
    FLAG_PROCEDURE_ABUSE,
    FLAG_RIGGED_COMPETITION,
    FLAG_SERIAL_WINNER,
    FLAG_TAILORED_SPEC,
    FLAG_THRESHOLD_MANIPULATION,
    FLAG_UNDERVALUED_SALE,
    FLAG_UNEXPLAINED_WEALTH,
    LEVEL_CORRUPTION,
    LEVEL_HIGH,
    LEVEL_LOW,
    LEVEL_NORMAL,
    LEVEL_SUSPICIOUS,
    SEVERITY_CRITICAL,
    SEVERITY_HIGH,
    SEVERITY_LOW,
    SEVERITY_MEDIUM,
    Flag,
    Signal,
)

# --- tunable weights (override via ANALYZER_WEIGHTS_PATH JSON) -------------- #

DEFAULT_FAMILY_WEIGHTS: dict[str, float] = {
    "competition": 1.2,
    "timing": 0.8,
    "thresholds": 1.0,
    "pricing": 1.1,
    "amendments": 0.9,
    "collusion": 1.4,
    "entities": 1.2,
    "lifecycle": 1.1,
    "specs": 1.3,  # spec-rigging (LLM)
    "scope": 1.1,  # implausible scope (LLM)
    "drug_pricing": 1.4,
    "inn_steering": 1.3,
    "jobs": 1.3,
    "assets": 1.2,
    "conflict": 1.3,
    "wealth": 1.4,
    "donations": 1.3,
}

# Logistic calibration (normalized so linear==0 -> score 0).
_K = 1.1
_X0 = 1.5

# Level band edges.
BANDS = [(85.0, LEVEL_CORRUPTION), (65.0, LEVEL_HIGH), (40.0, LEVEL_SUSPICIOUS), (20.0, LEVEL_LOW)]

# Which flag type a signal key rolls up into.
_KEY_TO_FLAG: dict[str, str] = {
    "single_bidder": FLAG_PROCEDURE_ABUSE,
    "few_bidders": FLAG_PROCEDURE_ABUSE,
    "non_open_procedure": FLAG_PROCEDURE_ABUSE,
    "complaint": FLAG_PROCEDURE_ABUSE,
    "short_submission_period": FLAG_PROCEDURE_ABUSE,
    "long_award_to_signature": FLAG_PROCEDURE_ABUSE,
    "late_bid_won": FLAG_COLLUSION,
    "just_under_threshold": FLAG_THRESHOLD_MANIPULATION,
    "repeated_sub_threshold_pair": FLAG_THRESHOLD_MANIPULATION,
    "price_outlier": FLAG_PRICE_DISCREPANCY,
    "round_amount": FLAG_PRICE_DISCREPANCY,
    "cost_overrun": FLAG_PRICE_DISCREPANCY,
    "overpricing_llm": FLAG_PRICE_DISCREPANCY,
    "contract_amended": FLAG_AMENDMENT_ABUSE,
    "delivery_discrepancy": FLAG_AMENDMENT_ABUSE,
    "amendment_abuse_llm": FLAG_AMENDMENT_ABUSE,
    "identical_bid_prices": FLAG_COLLUSION,
    "price_too_close": FLAG_COLLUSION,
    "same_submission_time": FLAG_COLLUSION,
    "loser_as_subcontractor": FLAG_COLLUSION,
    "bidders_shared_contact": FLAG_COLLUSION,
    "bid_rotation": FLAG_COLLUSION,
    "collusion_llm": FLAG_COLLUSION,
    "serial_winner": FLAG_SERIAL_WINNER,
    "buyer_dependence": FLAG_SERIAL_WINNER,
    "serial_winner_llm": FLAG_SERIAL_WINNER,
    "conflict_contact_overlap": FLAG_CONFLICT_OF_INTEREST,
    "possible_kinship": FLAG_CONFLICT_OF_INTEREST,
    "kinship_llm": FLAG_CONFLICT_OF_INTEREST,
    "conflict_of_interest_llm": FLAG_CONFLICT_OF_INTEREST,
    "debarred_winner": FLAG_CONFLICT_OF_INTEREST,
    "cancelled_procedure": FLAG_CANCELLED,
    "reissue_after_cancel": FLAG_CANCELLED,
    "cancellation_llm": FLAG_CANCELLED,
    "doc_clone": FLAG_DOC_CLONE,
    "tailored_spec_llm": FLAG_TAILORED_SPEC,
    "implausible_scope_llm": FLAG_IMPLAUSIBLE_SCOPE,
    "drug_above_ceiling": FLAG_DRUG_OVERPRICING,
    "drug_overpricing_llm": FLAG_DRUG_OVERPRICING,
    "repackaging_markup": FLAG_DRUG_OVERPRICING,
    "inn_steering_llm": FLAG_INN_STEERING,
    "short_application_window": FLAG_RIGGED_COMPETITION,
    "holiday_window": FLAG_RIGGED_COMPETITION,
    "rigged_competition_llm": FLAG_RIGGED_COMPETITION,
    "conflict_kinship_llm": FLAG_CONFLICT_OF_INTEREST,
    "asset_single_bidder": FLAG_UNDERVALUED_SALE,
    "asset_round_amount": FLAG_UNDERVALUED_SALE,
    "short_asset_notice": FLAG_UNDERVALUED_SALE,
    "undervalued_sale_llm": FLAG_UNDERVALUED_SALE,
    "magistrate_competition_llm": FLAG_RIGGED_COMPETITION,
    "unexplained_wealth_llm": FLAG_UNEXPLAINED_WEALTH,
    "late_declaration": FLAG_UNEXPLAINED_WEALTH,
    "missing_declaration": FLAG_UNEXPLAINED_WEALTH,
    "missing_declaration_date": FLAG_UNEXPLAINED_WEALTH,
    "incomplete_declaration_row": FLAG_UNEXPLAINED_WEALTH,
    "duplicate_magistrate_filings": FLAG_UNEXPLAINED_WEALTH,
    "donation_influence_llm": FLAG_DONOR_INFLUENCE,
    "quid_pro_quo_llm": FLAG_DONOR_INFLUENCE,
    "repeat_donor": FLAG_DONOR_INFLUENCE,
    "large_donation": FLAG_DONOR_INFLUENCE,
    "in_kind_donation": FLAG_DONOR_INFLUENCE,
    "missing_donor": FLAG_DONOR_INFLUENCE,
    "round_donation": FLAG_DONOR_INFLUENCE,
}


def load_family_weights(path: Path | None) -> dict[str, float]:
    weights = dict(DEFAULT_FAMILY_WEIGHTS)
    if path and path.exists():
        try:
            override = json.loads(path.read_text(encoding="utf-8"))
            weights.update({k: float(v) for k, v in override.items()})
        except (ValueError, OSError):
            pass
    return weights


def _logistic(x: float) -> float:
    return 1.0 / (1.0 + math.exp(-_K * (x - _X0)))


def _normalized_logistic(linear: float) -> float:
    floor = _logistic(0.0)
    return max(0.0, (_logistic(linear) - floor) / (1.0 - floor)) * 100.0


def _noisy_or(risks: list[float]) -> float:
    prod = 1.0
    for r in risks:
        prod *= 1.0 - max(0.0, min(1.0, r))
    return 1.0 - prod


def level_for(score: float, hard_tripped: bool) -> str:
    if hard_tripped:
        return LEVEL_CORRUPTION
    for edge, level in BANDS:
        if score >= edge:
            return level
    return LEVEL_NORMAL


def _severity_for(score: float, hard_tripped: bool) -> str:
    if hard_tripped or score >= 85:
        return SEVERITY_CRITICAL
    if score >= 65:
        return SEVERITY_HIGH
    if score >= 40:
        return SEVERITY_MEDIUM
    return SEVERITY_LOW


# --------------------------------------------------------------------------- #
# Hard-trip rules: (id, predicate, score, reason). Each needs strong signals.
# --------------------------------------------------------------------------- #


def _by_key(signals: list[Signal]) -> dict[str, Signal]:
    out: dict[str, Signal] = {}
    for s in signals:
        # keep the strongest instance per key
        if s.key not in out or s.risk > out[s.key].risk:
            out[s.key] = s
    return out


def _hard_trip(view: TenderView, by_key: dict[str, Signal]) -> tuple[bool, float, str]:
    def has(key: str, thr: float = 0.5) -> bool:
        return key in by_key and by_key[key].risk >= thr

    if has("conflict_contact_overlap"):
        return True, 100.0, "Изпълнителят споделя контакт с длъжностно лице на възложителя (пряк конфликт на интереси)."
    if has("identical_bid_prices", 0.7) and has("same_submission_time", 0.5):
        return True, 100.0, "Идентични цени и едновременно подаване на офертите — недвусмислена тръжна манипулация."
    if has("loser_as_subcontractor") and has("identical_bid_prices", 0.7):
        return True, 100.0, "Губещ с идентична цена е нает като подизпълнител — картелна схема."
    if has("single_bidder", 0.4) and has("tailored_spec_llm", 0.7):
        return True, 99.0, "Една оферта при условия, скроени по мярка — конкуренцията е елиминирана."
    if has("late_bid_won"):
        return True, 99.0, "Печелившата оферта е подадена след крайния срок."
    if has("possible_kinship") and has("buyer_dependence", 0.5):
        return True, 99.0, "Вероятна роднинска връзка и зависимост на възложителя от този изпълнител."
    if has("reissue_after_cancel") and has("tailored_spec_llm", 0.6):
        return True, 99.0, "Повторно пускане след прекратяване с условия по мярка."
    if has("drug_above_ceiling", 0.8) and has("drug_overpricing_llm", 0.7):
        return True, 100.0, "Цена на лекарство значително над NCPR предел с потвърждение от LLM."
    if has("drug_above_ceiling", 0.85):
        return True, 99.0, "Наблюдавана цена над пределната NCPR стойност."
    if has("rigged_competition_llm", 0.8) and has("short_application_window", 0.45):
        return True, 99.0, "Нагласен конкурс: кратък срок + условия по мярка."
    if has("conflict_kinship_llm", 0.85):
        return True, 99.0, "Документирана роднинска връзка при назначение/конкурс."
    if has("undervalued_sale_llm", 0.85) and has("asset_single_bidder", 0.4):
        return True, 99.0, "Занижена продажба на актив с един участник."
    if has("unexplained_wealth_llm", 0.85) and has("late_declaration", 0.3):
        return True, 99.0, "Необяснимо имущество с късно подадена декларация."
    if has("magistrate_competition_llm", 0.85) and has("short_application_window", 0.45):
        return True, 99.0, "Нагласен конкурс за магистрат: кратък срок + условия по мярка."
    if has("magistrate_competition_llm", 0.85) and has("conflict_kinship_llm", 0.7):
        return True, 99.0, "Нагласен конкурс за магистрат с документиран конфликт/роднина."
    if has("donation_influence_llm", 0.85) and has("repeat_donor", 0.5):
        return True, 99.0, "Повтарящ се дарител с високо съмнение за необосновано влияние (pay-to-play)."
    if has("donation_influence_llm", 0.85) and has("quid_pro_quo_llm", 0.5):
        return True, 99.0, "Дарение с документирана quid-pro-quo връзка към поръчка/договор."
    return False, 0.0, ""


def score_record(
    view: TenderView,
    signals: list[Signal],
    weights: dict[str, float] | None = None,
) -> tuple[float, str, bool, list[Signal], list[Flag], str]:
    """Return (score, level, hard_tripped, scored_signals, flags, hard_reason)."""
    weights = weights or DEFAULT_FAMILY_WEIGHTS
    by_key = _by_key(signals)

    # Annotate weight + indicative contribution on each signal (auditability).
    scored: list[Signal] = []
    by_family: dict[str, list[float]] = defaultdict(list)
    for s in signals:
        w = weights.get(s.family, 1.0)
        s.weight = w
        s.contribution = round(w * s.risk, 4)
        by_family[s.family].append(s.risk)
        scored.append(s)

    tripped, trip_score, trip_reason = _hard_trip(view, by_key)

    if tripped:
        score = trip_score
    else:
        linear = sum(weights.get(f, 1.0) * _noisy_or(rs) for f, rs in by_family.items())
        strong_families = sum(1 for rs in by_family.values() if _noisy_or(rs) >= 0.5)
        if strong_families >= 4:
            linear += 1.0
        elif strong_families >= 3:
            linear += 0.5
        score = round(_normalized_logistic(linear), 1)

    level = level_for(score, tripped)
    flags = _build_flags(view, scored, by_family, weights, score, tripped, trip_reason)
    return score, level, tripped, scored, flags, trip_reason


def _build_flags(
    view: TenderView,
    signals: list[Signal],
    by_family: dict[str, list[float]],
    weights: dict[str, float],
    score: float,
    hard_tripped: bool,
    hard_reason: str,
) -> list[Flag]:
    # Iron rule: no source_url -> no flag.
    if not view.source_url:
        return []

    subject = view.winner_name or view.buyer_name or view.title or view.natural_key
    grouped: dict[str, list[Signal]] = defaultdict(list)
    for s in signals:
        ftype = _KEY_TO_FLAG.get(s.key)
        if ftype and s.risk >= 0.3:
            grouped[ftype].append(s)

    flags: list[Flag] = []
    for ftype, group in grouped.items():
        group.sort(key=lambda s: s.risk, reverse=True)
        max_risk = group[0].risk
        severity = _severity_for(score if hard_tripped else max_risk * 100, hard_tripped)
        reasons = "; ".join(dict.fromkeys(s.rationale_bg for s in group if s.rationale_bg))
        flags.append(
            Flag(
                type=ftype,
                severity=severity,
                subject=subject,
                source_urls=[view.source_url],
                explanation_bg=reasons,
                evidence={
                    "signals": [
                        {"key": s.key, "code": s.code, "risk": s.risk, "value": s.value}
                        for s in group
                    ]
                },
            )
        )
    if hard_tripped and hard_reason and not flags:
        flags.append(
            Flag(
                type=FLAG_CONFLICT_OF_INTEREST,
                severity=SEVERITY_CRITICAL,
                subject=subject,
                source_urls=[view.source_url],
                explanation_bg=hard_reason,
                evidence={},
            )
        )
    return flags
