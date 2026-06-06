"""Project abuse agent: motorway/road infrastructure programmes (АПИ, МРРБ)."""

from __future__ import annotations

import re

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import ProjectAbuseOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "project_abuse"

_STATUS_RE = re.compile(r"статус\s*:\s*([^.;]+)", re.IGNORECASE)
_FUNDING_RE = re.compile(r"финансиране\s*:\s*([^.;]+)", re.IGNORECASE)
_LOT_RE = re.compile(r"(лот|участък)\s*[\d\w./-]+", re.IGNORECASE)
_MOTORWAY_RE = re.compile(r"автомагистрал[аи]", re.IGNORECASE)


def _project_block(view: TenderView) -> str:
    blob = f"{view.title}\n{view.full_text}"
    payload = view.payload
    lines = ["--- Инфраструктурен проект ---"]
    status = payload.get("status")
    funding = payload.get("funding_source")
    if not status:
        m = _STATUS_RE.search(blob)
        status = m.group(1).strip() if m else None
    if not funding:
        m = _FUNDING_RE.search(blob)
        funding = m.group(1).strip() if m else None
    lines.append(f"Статус: {status or '(няма)'}")
    lines.append(f"Финансиране: {funding or '(няма)'}")
    lot = _LOT_RE.search(blob)
    if lot:
        lines.append(f"Лот/участък: {lot.group(0)}")
    if _MOTORWAY_RE.search(blob):
        lines.append("Тип: автомагистрала / магистрален проект")
    return "\n".join(lines)


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> ProjectAbuseOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{_project_block(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, ProjectAbuseOutput)


def signals(output: ProjectAbuseOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.abuse_confidence >= 0.3:
        out.append(
            Signal(
                key="project_abuse_llm",
                family="projects",
                code="RD01",
                risk=output.abuse_confidence,
                value={
                    "project": output.project_name or view.title[:80],
                    "delay_pattern": output.delay_pattern,
                    "funding_anomaly": output.funding_anomaly,
                    "contractor_lock_in": output.contractor_lock_in,
                },
                source_field="инфраструктурен проект (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за злоупотреба при пътен инфраструктурен проект.",
            )
        )
    return out
