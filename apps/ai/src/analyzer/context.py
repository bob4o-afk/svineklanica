"""Cross-record context: the aggregates single-record signals need.

Serial-winner streaks, buyer->supplier concentration, price peers per CPV
category, and (optionally) embedding-based "similar tender" lookup for
re-tender / doc-clone detection are all *cross-record* — they need a view over
the whole corpus, not just the record being scored.
"""

from __future__ import annotations

import logging
import statistics
from collections import defaultdict
from dataclasses import dataclass, field

from scraper.corpus import iter_normalized
from scraper.normalize import is_valid_eik, normalize_company_name

from .payload import TenderView, view_from_record

logger = logging.getLogger(__name__)


@dataclass
class DrugReference:
    inn: str
    product: str
    ceiling: float | None
    reimbursement: float | None
    holder: str = ""


def _amount_of(money: object) -> float | None:
    if isinstance(money, dict):
        amt = money.get("amount")
        if isinstance(amt, (int, float)):
            return float(amt)
    if isinstance(money, (int, float)):
        return float(money)
    return None


def _index_drug(payload: dict, index: dict[str, DrugReference]) -> None:
    inn = (payload.get("inn") or "").strip()
    product = (payload.get("product") or "").strip()
    ceiling = _amount_of(payload.get("price_ceiling"))
    reimbursement = _amount_of(payload.get("price_reimbursement"))
    holder = (payload.get("holder") or "").strip()
    ref = DrugReference(
        inn=inn,
        product=product,
        ceiling=ceiling,
        reimbursement=reimbursement,
        holder=holder,
    )
    for key in {inn.lower(), product.lower(), " ".join(product.lower().split())}:
        if key:
            index[key] = ref


def entity_key(name: str, eik: str | None) -> str:
    """Stable key for a company/person: EIK when valid, else normalized name."""
    if eik and is_valid_eik(eik):
        return f"eik:{eik}"
    norm = normalize_company_name(name)
    return f"name:{norm}" if norm else ""


@dataclass
class AnalysisContext:
    views: list[TenderView] = field(default_factory=list)
    winner_counts: dict[str, int] = field(default_factory=dict)
    authority_counts: dict[str, int] = field(default_factory=dict)
    pair_counts: dict[tuple[str, str], int] = field(default_factory=dict)
    cpv_values: dict[str, list[float]] = field(default_factory=dict)
    drug_index: dict[str, DrugReference] = field(default_factory=dict)
    total_records: int = 0

    # Optional embedding index for similar-tender lookup (built lazily).
    _embedder: object | None = None
    _vectors: list[list[float]] | None = None

    # ---- aggregate lookups -------------------------------------------- #

    def winner_win_count(self, view: TenderView) -> int:
        return self.winner_counts.get(entity_key(view.winner_name, view.winner_eik), 0)

    def authority_record_count(self, view: TenderView) -> int:
        return self.authority_counts.get(entity_key(view.buyer_name, view.buyer_eik), 0)

    def pair_count(self, view: TenderView) -> int:
        wk = entity_key(view.winner_name, view.winner_eik)
        ak = entity_key(view.buyer_name, view.buyer_eik)
        if not wk or not ak:
            return 0
        return self.pair_counts.get((ak, wk), 0)

    def buyer_dependence(self, view: TenderView) -> float:
        """Share of an authority's records that go to this winner (0..1)."""
        total = self.authority_record_count(view)
        if total <= 0:
            return 0.0
        return self.pair_count(view) / total

    def cpv_price_stats(self, view: TenderView) -> tuple[float | None, float | None, int]:
        """Return (median, mad, n) of awarded values in this CPV division."""
        div = view.cpv_division
        if not div:
            return None, None, 0
        values = self.cpv_values.get(div, [])
        if len(values) < 4:
            return None, None, len(values)
        median = statistics.median(values)
        mad = statistics.median([abs(v - median) for v in values]) or 0.0
        return median, mad, len(values)

    # ---- embeddings (optional) ---------------------------------------- #

    def build_embeddings(self, embedder) -> None:  # noqa: ANN001
        try:
            texts = [v.full_text or v.title for v in self.views]
            self._vectors = embedder.embed_documents(texts)
            self._embedder = embedder
        except Exception:  # noqa: BLE001 - embeddings are a nice-to-have
            logger.warning("Embedding index unavailable; similar-tender lookup disabled.")
            self._vectors = None
            self._embedder = None

    def most_similar(self, index: int, *, top_k: int = 3) -> list[tuple[int, float]]:
        """Indices + cosine score of the most similar OTHER views."""
        if not self._vectors:
            return []
        target = self._vectors[index]
        scored: list[tuple[int, float]] = []
        for i, vec in enumerate(self._vectors):
            if i == index:
                continue
            scored.append((i, _cosine(target, vec)))
        scored.sort(key=lambda t: t[1], reverse=True)
        return scored[:top_k]


def _cosine(a: list[float], b: list[float]) -> float:
    dot = sum(x * y for x, y in zip(a, b))
    return float(dot)  # embedders return L2-normalized vectors


def build_context(config, sources: list[str]) -> AnalysisContext:  # noqa: ANN001
    """Read the corpus for ``sources`` and compute the aggregates."""
    records: list[dict] = []
    for source in sources:
        records.extend(iter_normalized(config.scraper, source))
    return context_from_records(records)


def context_from_records(records: list[dict]) -> AnalysisContext:
    """Build a context (views + aggregates) from in-memory ingest records."""
    ctx = AnalysisContext()
    drug_index: dict[str, DrugReference] = {}

    for record in records:
        if record.get("source") == "ncpr":
            payload = record.get("payload") or {}
            _index_drug(payload, drug_index)
            continue

        view = view_from_record(record)
        view.corpus_index = len(ctx.views)
        ctx.views.append(view)

    ctx.drug_index = drug_index

    winner_counts: dict[str, int] = defaultdict(int)
    authority_counts: dict[str, int] = defaultdict(int)
    pair_counts: dict[tuple[str, str], int] = defaultdict(int)
    cpv_values: dict[str, list[float]] = defaultdict(list)

    for v in ctx.views:
        wk = entity_key(v.winner_name, v.winner_eik)
        ak = entity_key(v.buyer_name, v.buyer_eik)
        if wk:
            winner_counts[wk] += 1
        if ak:
            authority_counts[ak] += 1
        if wk and ak:
            pair_counts[(ak, wk)] += 1
        if v.cpv_division and v.value_amount and v.value_amount > 0:
            cpv_values[v.cpv_division].append(v.value_amount)

    ctx.winner_counts = dict(winner_counts)
    ctx.authority_counts = dict(authority_counts)
    ctx.pair_counts = dict(pair_counts)
    ctx.cpv_values = dict(cpv_values)
    ctx.total_records = len(ctx.views)
    return ctx
