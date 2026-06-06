"""Opt-in live Gemini test. Run with: uv run pytest --run-llm

Makes ONE real structured call to verify the key, model id, and json_schema
path actually work end to end. Skipped by default (zero tokens in normal runs).
"""

from __future__ import annotations

import pytest

from analyzer.config import load_config
from analyzer.context import context_from_records
from analyzer.llm import build_client
from analyzer.orchestrator import analyze_view

from conftest import make_record


@pytest.mark.llm
def test_live_gemini_scores_a_rigged_record():
    config = load_config()
    client = build_client(config)
    assert client.available, "GOOGLE_API_KEY missing or client unavailable"

    rec = make_record(
        natural_key="LIVE-1",
        title="Доставка на софтуер",
        bids_count=1,
        procedure_type="пряко договаряне без предварително обявление",
        winner={"name": "ЕДИНСТВЕН ЕООД"},
        full_text=(
            "Изисква се точно 10 души, сертифицирани със Spring Boot, и телефонна "
            "централа, която разделя записите на два канала. Изисква се точно "
            "определен надпис на точно определено място."
        ),
    )
    ctx = context_from_records([rec])
    verdict = analyze_view(ctx.views[0], ctx, client, model_name=config.model)

    assert 0 <= verdict.corruption_score <= 100
    assert verdict.level
    assert verdict.explanation_bg
    # A blatantly rigged single-bid record should not score as clean.
    assert verdict.corruption_score >= 40
