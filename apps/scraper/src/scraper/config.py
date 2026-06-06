"""Runtime configuration for the scraper, loaded from the environment.

Reads the polite-scraper knobs and the per-source enable flags / base URLs from
env (see ``apps/scraper/.env.example``). The set of source ``base_url`` hosts is
the SSRF allow-list (``.claude/rules/security.md`` §4): the HTTP client refuses
to fetch any host that is not (a subdomain of) an allow-listed source domain.

No secrets live here. Every value is either a public base URL or an operational
knob; real ``.env`` files are gitignored.
"""

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import urlparse

DEFAULT_USER_AGENT = (
    "LiberHack-Watchdog/0.1 (+https://github.com/BabyNejii/corruption-fucker)"
)

# Known public procurement source domains. The env may override the base URL or
# toggle a source on/off, but these are the defaults the scraper ships with.
SOURCE_DEFAULTS: dict[str, str] = {
    "egov": "https://data.egov.bg",
    "caiseop": "https://data.egov.bg",
    "ted": "https://ted.europa.eu",
    "aop": "https://www.aop.bg",
    "eop": "https://app.eop.bg",
    "isun": "https://2020.eufunds.bg",
    "sebra": "https://minfin.bg",
    "ncpr": "https://www.ncpr.bg",
    "nhif": "https://www.nhif.bg",
    "mz": "https://www.mh.government.bg",
    "mz_jobs": "https://www.mh.government.bg",
    "mz_assets": "https://www.mh.government.bg",
    "vss": "https://profile-op.vss.justice.bg",
    "prb": "https://prb.bg",
    "vss_jobs": "https://vss.justice.bg",
    "ivss_declarations": "https://www.inspectoratvss.bg",
    "mjs_assets": "https://www.mjs.bg",
    "mvr": "https://mvr.bg",
    "mvr_donations": "https://mvr.bg",
    "mvr_jobs": "https://mvr.bg",
    "mvr_assets": "https://mvr.bg",
    "gov_tenders": "https://www.government.bg",
    "gov_jobs": "https://iisda.government.bg",
    "gov_audits": "https://www.bulnao.government.bg",
    "gov_declarations": "https://register.antikorupcia.bg",
    "gov_concessions": "https://nkr.government.bg",
    "api_tenders": "https://www.api.bg",
    "api_jobs": "https://www.api.bg",
    "api_projects": "https://www.api.bg",
    "mrrb_tenders": "https://www.mrrb.bg",
    "avtomagistrali_tenders": "https://avtomagistrali.com",
}


def _repo_storage_ingest() -> Path:
    """Default ingest dir: ``<repo root>/storage/ingest`` (shared with Laravel).

    In Docker this is overridden by ``INGEST_OUT_DIR=/data/ingest`` (the bind
    mount). Locally we resolve it relative to this file so ``uv run scrape``
    works with no env at all.
    """
    # .../apps/scraper/src/scraper/config.py -> parents[4] == repo root
    return Path(__file__).resolve().parents[4] / "storage" / "ingest"


def _env_bool(name: str, default: bool = False) -> bool:
    raw = os.environ.get(name)
    if raw is None:
        return default
    return raw.strip().lower() in {"1", "true", "yes", "on"}


def _env_float(name: str, default: float) -> float:
    raw = os.environ.get(name)
    if raw is None or not raw.strip():
        return default
    try:
        return float(raw)
    except ValueError:
        return default


@dataclass(frozen=True)
class SourceConfig:
    """Per-source toggle + base URL (the only domain that source may fetch)."""

    id: str
    enabled: bool
    base_url: str

    @property
    def host(self) -> str:
        return (urlparse(self.base_url).hostname or "").lower()


@dataclass(frozen=True)
class Config:
    ingest_out_dir: Path
    user_agent: str
    request_timeout_s: float
    rate_limit_rps: float
    sources: dict[str, SourceConfig]

    @property
    def normalized_dir(self) -> Path:
        return self.ingest_out_dir / "normalized"

    @property
    def raw_dir(self) -> Path:
        return self.ingest_out_dir / "raw"

    @property
    def samples_dir(self) -> Path:
        return self.ingest_out_dir / "samples"

    @property
    def embeddings_dir(self) -> Path:
        return self.ingest_out_dir / "embeddings"

    @property
    def cache_dir(self) -> Path:
        return self.ingest_out_dir / ".cache"

    def source(self, source_id: str) -> SourceConfig:
        try:
            return self.sources[source_id]
        except KeyError as exc:
            known = ", ".join(sorted(self.sources)) or "(none)"
            raise KeyError(f"Unknown source '{source_id}'. Known: {known}") from exc

    def allow_listed_hosts(self) -> set[str]:
        """Registered hosts the HTTP client is allowed to fetch from."""
        return {s.host for s in self.sources.values() if s.host}


def load_config() -> Config:
    """Build a :class:`Config` from the current environment."""
    ingest_dir = os.environ.get("INGEST_OUT_DIR")
    ingest_out_dir = Path(ingest_dir) if ingest_dir else _repo_storage_ingest()

    sources: dict[str, SourceConfig] = {}
    for source_id, default_url in SOURCE_DEFAULTS.items():
        prefix = source_id.upper()
        base_url = os.environ.get(f"{prefix}_BASE_URL", default_url)
        enabled = _env_bool(f"{prefix}_ENABLED", default=False)
        sources[source_id] = SourceConfig(id=source_id, enabled=enabled, base_url=base_url)

    return Config(
        ingest_out_dir=ingest_out_dir,
        user_agent=os.environ.get("USER_AGENT", DEFAULT_USER_AGENT),
        request_timeout_s=_env_float("REQUEST_TIMEOUT_S", 30.0),
        rate_limit_rps=_env_float("RATE_LIMIT_RPS", 1.0),
        sources=sources,
    )
