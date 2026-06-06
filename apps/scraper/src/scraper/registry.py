"""Source registry — maps a source id to its :class:`Source` implementation."""

from __future__ import annotations

from .sources.aop import AopSource
from .sources.api_jobs import ApiJobsSource
from .sources.api_projects import ApiProjectsSource
from .sources.api_tenders import ApiTendersSource
from .sources.avtomagistrali_tenders import AvtomagistraliTendersSource
from .sources.base import Source
from .sources.caiseop import CaiseopSource
from .sources.egov import EgovSource
from .sources.eop import EopSource
from .sources.gov_audits import GovAuditsSource
from .sources.gov_concessions import GovConcessionsSource
from .sources.gov_declarations import GovDeclarationsSource
from .sources.gov_jobs import GovJobsSource
from .sources.gov_tenders import GovTendersSource
from .sources.isun import IsunSource
from .sources.ivss_declarations import IvssDeclarationsSource
from .sources.mjs_assets import MjsAssetsSource
from .sources.mrrb_tenders import MrrbTendersSource
from .sources.mvr import MvrSource
from .sources.mvr_assets import MvrAssetsSource
from .sources.mvr_donations import MvrDonationsSource
from .sources.mvr_jobs import MvrJobsSource
from .sources.mz import MzSource
from .sources.mz_assets import MzAssetsSource
from .sources.mz_jobs import MzJobsSource
from .sources.ncpr import NcprSource
from .sources.nhif import NhifSource
from .sources.prb import PrbSource
from .sources.sebra import SebraSource
from .sources.ted import TedSource
from .sources.vss import VssSource
from .sources.vss_jobs import VssJobsSource

SOURCES: dict[str, type[Source]] = {
    EgovSource.id: EgovSource,
    CaiseopSource.id: CaiseopSource,
    TedSource.id: TedSource,
    AopSource.id: AopSource,
    SebraSource.id: SebraSource,
    EopSource.id: EopSource,
    IsunSource.id: IsunSource,
    NcprSource.id: NcprSource,
    NhifSource.id: NhifSource,
    MzSource.id: MzSource,
    MzJobsSource.id: MzJobsSource,
    MzAssetsSource.id: MzAssetsSource,
    VssSource.id: VssSource,
    PrbSource.id: PrbSource,
    VssJobsSource.id: VssJobsSource,
    IvssDeclarationsSource.id: IvssDeclarationsSource,
    MjsAssetsSource.id: MjsAssetsSource,
    MvrSource.id: MvrSource,
    MvrDonationsSource.id: MvrDonationsSource,
    MvrJobsSource.id: MvrJobsSource,
    MvrAssetsSource.id: MvrAssetsSource,
    GovTendersSource.id: GovTendersSource,
    GovJobsSource.id: GovJobsSource,
    GovAuditsSource.id: GovAuditsSource,
    GovDeclarationsSource.id: GovDeclarationsSource,
    GovConcessionsSource.id: GovConcessionsSource,
    ApiTendersSource.id: ApiTendersSource,
    ApiJobsSource.id: ApiJobsSource,
    ApiProjectsSource.id: ApiProjectsSource,
    MrrbTendersSource.id: MrrbTendersSource,
    AvtomagistraliTendersSource.id: AvtomagistraliTendersSource,
}


def get_source_class(source_id: str) -> type[Source]:
    try:
        return SOURCES[source_id]
    except KeyError as exc:
        known = ", ".join(sorted(SOURCES))
        raise KeyError(f"Unknown source '{source_id}'. Known: {known}") from exc


def all_source_ids() -> list[str]:
    return list(SOURCES)
