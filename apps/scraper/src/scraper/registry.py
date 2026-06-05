"""Source registry — maps a source id to its :class:`Source` implementation."""

from __future__ import annotations

from .sources.aop import AopSource
from .sources.base import Source
from .sources.caiseop import CaiseopSource
from .sources.egov import EgovSource
from .sources.eop import EopSource
from .sources.isun import IsunSource
from .sources.sebra import SebraSource
from .sources.ted import TedSource

SOURCES: dict[str, type[Source]] = {
    EgovSource.id: EgovSource,
    CaiseopSource.id: CaiseopSource,
    TedSource.id: TedSource,
    AopSource.id: AopSource,
    SebraSource.id: SebraSource,
    EopSource.id: EopSource,
    IsunSource.id: IsunSource,
}


def get_source_class(source_id: str) -> type[Source]:
    try:
        return SOURCES[source_id]
    except KeyError as exc:
        known = ", ".join(sorted(SOURCES))
        raise KeyError(f"Unknown source '{source_id}'. Known: {known}") from exc


def all_source_ids() -> list[str]:
    return list(SOURCES)
