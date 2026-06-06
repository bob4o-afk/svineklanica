from __future__ import annotations

import pytest

from analyzer.agents.base import load_prompt

PROMPTS = [
    "spec_rigging",
    "scope",
    "lifecycle",
    "entity",
    "collusion",
    "aggregator",
    "category_router",
    "drug_overpricing",
    "inn_steering",
    "rigged_competition",
    "conflict_kinship",
    "undervalued_sale",
    "magistrate_competition",
    "unexplained_wealth",
    "judiciary_category_router",
    "donation_influence",
    "police_category_router",
]


@pytest.mark.parametrize("name", PROMPTS)
def test_prompt_loads_and_is_markdown(name):
    text = load_prompt(name)
    assert text.strip()
    assert "# Роля" in text  # all prompts define a role in Markdown
    assert "JSON" in text  # all instruct structured JSON output
