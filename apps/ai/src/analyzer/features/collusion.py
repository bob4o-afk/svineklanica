"""Collusion / bid-rigging red flags (R024, R028, R034, R042, R070).

These need per-bidder data (prices, submission timestamps). When the source
carries no bidder list they simply produce nothing. Bulgarian КЗК cartel cases
repeatedly cite near-identical prices and identical submission dates/times in
ЦАИС ЕОП — both captured here.
"""

from __future__ import annotations

from ..context import AnalysisContext
from ..payload import TenderView
from ..schemas import Signal
from .base import clamp01, signal

FAMILY = "collusion"


def extract(view: TenderView, ctx: AnalysisContext) -> list[Signal]:
    out: list[Signal] = []
    bidders = [b for b in view.bidders if b is not None]
    amounts = [b.amount for b in bidders if b.amount and b.amount > 0]

    # R028 - identical bid prices between supposedly competing bidders.
    if len(amounts) >= 2 and len(set(round(a, 2) for a in amounts)) < len(amounts):
        out.append(
            signal(
                "identical_bid_prices",
                FAMILY,
                0.8,
                code="R028",
                value=sorted(amounts),
                source_field="bidders.amount",
                rationale_bg="Идентични ценови предложения между 'конкуренти' — белег за тръжна манипулация.",
            )
        )

    # R024 - losing price suspiciously close to the winning one (cover bidding).
    if len(amounts) >= 2:
        srt = sorted(amounts)
        low, second = srt[0], srt[1]
        if low > 0 and (second - low) / low < 0.01:
            out.append(
                signal(
                    "price_too_close",
                    FAMILY,
                    0.6,
                    code="R024",
                    value={"winning": low, "next": second},
                    source_field="bidders.amount",
                    rationale_bg="Втората оферта е на <1% от печелившата (възможно прикриващо наддаване).",
                )
            )

    # R034 - identical submission timestamps (the ЦАИС ЕОП tell).
    times = [b.submitted_at for b in bidders if b.submitted_at]
    if len(times) >= 2 and len({t.replace(microsecond=0) for t in times}) < len(times):
        out.append(
            signal(
                "same_submission_time",
                FAMILY,
                0.7,
                code="R034",
                value=[t.isoformat() for t in times],
                source_field="bidders.submitted_at",
                rationale_bg="Оферти с идентични дати/часове на подаване — координация между участници.",
            )
        )

    # R070 - a losing bidder is hired as a subcontractor.
    if view.subcontractors and bidders:
        losers = {b.name.strip().lower() for b in bidders if b.disqualified or not _is_winner(view, b)}
        subs = {s.strip().lower() for s in view.subcontractors}
        hit = losers & subs
        if hit:
            out.append(
                signal(
                    "loser_as_subcontractor",
                    FAMILY,
                    0.6,
                    code="R070",
                    value=sorted(hit),
                    source_field="subcontractors",
                    rationale_bg="Губещ оферент е нает като подизпълнител — типична картелна схема.",
                )
            )

    # R042 - bidders share an address or phone (apparent connection).
    if _bidders_share_contact(view):
        out.append(
            signal(
                "bidders_shared_contact",
                FAMILY,
                0.75,
                code="R042",
                value=True,
                source_field="bidders.address/phone",
                rationale_bg="'Конкуриращи се' оференти споделят адрес/телефон — вероятно свързани лица.",
            )
        )

    # R057 - bid rotation across the corpus (same group takes turns winning).
    rotation = _rotation_risk(view, ctx)
    if rotation > 0:
        out.append(
            signal(
                "bid_rotation",
                FAMILY,
                clamp01(rotation),
                code="R057",
                value=ctx.pair_count(view),
                source_field="authority+winner history",
                rationale_bg="Повтаряща се двойка възложител↔печеливш — възможна ротация на победители.",
            )
        )

    return out


def _is_winner(view: TenderView, bidder) -> bool:  # noqa: ANN001
    return bool(view.winner_name) and bidder.name.strip().lower() == view.winner_name.strip().lower()


def _bidders_share_contact(view: TenderView) -> bool:
    raw = view.payload.get("bidders") or view.payload.get("offers")
    if not isinstance(raw, list):
        return False
    addrs: list[str] = []
    phones: list[str] = []
    for item in raw:
        if isinstance(item, dict):
            if item.get("address"):
                addrs.append(str(item["address"]).strip().lower())
            if item.get("phone"):
                phones.append(str(item["phone"]).strip().lower())
    return (len(addrs) >= 2 and len(set(addrs)) < len(addrs)) or (
        len(phones) >= 2 and len(set(phones)) < len(phones)
    )


def _rotation_risk(view: TenderView, ctx: AnalysisContext) -> float:
    pair = ctx.pair_count(view)
    authority_total = ctx.authority_record_count(view)
    if pair >= 4 and authority_total >= pair:
        return 0.3 + 0.1 * (pair - 4)
    return 0.0
