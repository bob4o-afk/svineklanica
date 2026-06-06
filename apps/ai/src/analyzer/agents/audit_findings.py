"""Audit findings agent: State Audit Office (Сметна палата) reports."""

from __future__ import annotations

from ..context import AnalysisContext
from ..llm import StructuredLLM
from ..payload import TenderView
from ..schemas import AuditFindingsOutput, Signal
from .base import full_text_block, load_prompt, tender_brief

NAME = "audit_findings"


def run(client: StructuredLLM, view: TenderView, ctx: AnalysisContext) -> AuditFindingsOutput | None:
    if not client.available:
        return None
    system = load_prompt(NAME)
    user = f"{tender_brief(view)}\n\n{full_text_block(view)}"
    return client.analyze(system, user, AuditFindingsOutput)


def signals(output: AuditFindingsOutput | None, view: TenderView) -> list[Signal]:
    if output is None:
        return []
    out: list[Signal] = []
    if output.findings_severity >= 0.3:
        out.append(
            Signal(
                key="audit_findings_llm",
                family="audits",
                code="GOV01",
                risk=output.findings_severity,
                value={
                    "institution": output.named_institution,
                    "repeat_target": output.repeat_target,
                    "unimplemented": output.unimplemented_recommendations,
                },
                source_field="одитен доклад (LLM)",
                rationale_bg=output.rationale_bg or "Съмнение за сериозни одитни констатации.",
            )
        )
    return out
