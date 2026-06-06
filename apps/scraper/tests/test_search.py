"""Ranking math + an end-to-end hashing-embedder search over a small corpus."""

from __future__ import annotations

from scraper.embeddings import HashingEmbedder
from scraper.search import rank
from scraper.sinks.embeddings import EmbedRecord


def _rec(key: str, embedding: list[float], text: str = "") -> EmbedRecord:
    return EmbedRecord(source="ted", natural_key=key, source_url=f"https://x/{key}",
                       model="m", dim=len(embedding), text=text, embedding=embedding)


def test_rank_orders_by_cosine():
    records = [
        _rec("a", [1.0, 0.0]),
        _rec("b", [0.0, 1.0]),
        _rec("c", [0.9, 0.1]),
    ]
    hits = rank([1.0, 0.0], records, top=3)
    assert [h.record.natural_key for h in hits] == ["a", "c", "b"]
    assert hits[0].score > hits[1].score > hits[2].score


def test_rank_top_k_limit():
    records = [_rec(str(i), [float(i), 1.0]) for i in range(20)]
    assert len(rank([1.0, 1.0], records, top=5)) == 5


def test_rank_empty_corpus():
    assert rank([1.0, 0.0], [], top=5) == []


def test_end_to_end_hashing_search_finds_relevant_doc():
    embedder = HashingEmbedder(dim=512)
    corpus = {
        "med": "Доставка на медицинско оборудване за болница УМБАЛ",
        "road": "Ремонт на улична настилка и пътища в община Сливен",
        "food": "Доставка на хранителни продукти за детски градини",
    }
    records = []
    for key, text in corpus.items():
        vec = embedder.embed_documents([text])[0]
        records.append(_rec(key, vec, text=text))

    hits = rank(embedder.embed_query("болнично медицинско оборудване"), records, top=3)
    assert hits[0].record.natural_key == "med"
