"""CLI entry points.

``analyze``      — batch: score every record of a source, write the verdict sidecar.
``analyze-one``  — score a single record (by natural_key or JSON on stdin) and
                   print one verdict JSON. This is what the backend control panel
                   calls to trigger the AI on demand.
"""

from __future__ import annotations

import argparse
import json
import logging
import sys
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed

from langchain_core.runnables import RunnableParallel
from scraper.config import SOURCE_DEFAULTS
from scraper.corpus import iter_normalized

from .config import AnalyzerConfig, load_config
from .context import AnalysisContext, build_context, context_from_records
from .flows import get_flow
from .llm import build_client, configure_agent_cap, configure_eval_budget
from .orchestrator import _build_agent_runnable, analyze_view
from .payload import TenderView, view_from_record
from .routing import resolve_sphere, route_flow
from .schemas import VerdictRecord
from .scoring import load_family_weights
from .sinks import VerdictSink
from .spheres import (
    HEALTHCARE_SOURCES,
    SPHERE_CLI_ALIASES,
    SPHERE_HEALTHCARE,
    sources_for_sphere,
)

logger = logging.getLogger("analyzer")


def _setup_logging() -> None:
    logging.basicConfig(level=logging.INFO, format="%(levelname)s %(name)s: %(message)s")
    for noisy in ("httpx", "httpcore", "google", "urllib3"):
        logging.getLogger(noisy).setLevel(logging.WARNING)
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]


def _maybe_embeddings(config: AnalyzerConfig, ctx: AnalysisContext, enabled: bool) -> None:
    if not enabled or not ctx.views:
        return
    try:
        from scraper.embeddings import get_embedder

        ctx.build_embeddings(get_embedder())
    except Exception:  # noqa: BLE001 - similarity is optional
        logger.warning("Embeddings unavailable; re-tender / doc-clone detection limited.")


def _build_sphere_context(config: AnalyzerConfig, sources: frozenset[str]) -> AnalysisContext:
    records: list[dict] = []
    for source in sorted(sources):
        records.extend(iter_normalized(config.scraper, source))
    return context_from_records(records)


def _build_healthcare_context(config: AnalyzerConfig) -> AnalysisContext:
    return _build_sphere_context(config, HEALTHCARE_SOURCES)


def _is_sphere_view(view: TenderView, sphere: str) -> bool:
    if view.payload.get("sphere") == sphere:
        return True
    return view.source in sources_for_sphere(sphere)


def _is_healthcare_view(view: TenderView) -> bool:
    return _is_sphere_view(view, SPHERE_HEALTHCARE)


def _runnable_cache(
    client,
    ctx: AnalysisContext,
    cache: dict[str, RunnableParallel],
    flow_key: str,
    sphere: str | None,
) -> RunnableParallel:
    cache_key = f"{sphere or ''}:{flow_key}"
    if cache_key not in cache:
        flow = get_flow(flow_key, sphere)
        cache[cache_key] = _build_agent_runnable(client, ctx, flow.agents)
    return cache[cache_key]


def _analyze_views(
    views: list[TenderView],
    ctx: AnalysisContext,
    client,
    config: AnalyzerConfig,
    weights,
    *,
    default_sphere: str | None = None,
) -> list[VerdictRecord]:
    """Score every view, concurrently, bounded by AGENTS_CAP.

    Order is preserved. Per-record routing + runnable selection happen inside
    each worker; the shared runnable cache is guarded by a lock. The hard ceiling
    on in-flight Gemini calls (and the total-evaluation budget) is enforced in
    ``llm.py`` via the governors configured in ``main``/``main_one``.
    """
    cache: dict[str, RunnableParallel] = {}
    cache_lock = threading.Lock()

    def work(view: TenderView) -> VerdictRecord:
        sphere = resolve_sphere(view) or default_sphere
        fk = route_flow(view, client, sphere)
        with cache_lock:
            runnable = _runnable_cache(client, ctx, cache, fk, sphere)
        return analyze_view(
            view,
            ctx,
            client,
            model_name=config.model,
            weights=weights,
            agent_runnable=runnable,
            flow_key=fk,
        )

    cap = max(1, config.agents_cap)
    if cap == 1 or len(views) <= 1:
        out: list[VerdictRecord] = []
        for v in views:
            try:
                out.append(work(v))
            except Exception:  # noqa: BLE001 - one bad record must not kill the batch
                # No verdict -> the Laravel ingest gate (--require-verdict) drops it,
                # so a failed evaluation is never stored. Logged so it's not silent.
                logger.exception("analyze failed for %s; dropped (won't be ingested)", v.natural_key)
        return out

    verdicts: list[VerdictRecord | None] = [None] * len(views)
    with ThreadPoolExecutor(max_workers=cap, thread_name_prefix="analyze") as executor:
        futures = {executor.submit(work, view): i for i, view in enumerate(views)}
        for future in as_completed(futures):
            i = futures[future]
            try:
                verdicts[i] = future.result()
            except Exception:  # noqa: BLE001 - one bad record must not kill the batch
                logger.exception("analyze failed for %s; dropped (won't be ingested)", views[i].natural_key)
    return [v for v in verdicts if v is not None]


def _analyze_sphere_batch(
    config: AnalyzerConfig,
    client,
    weights,
    sink: VerdictSink,
    sphere: str,
    sources: list[str],
    limit: int | None,
    sample_size: int,
    no_embed: bool,
) -> None:
    ctx = _build_sphere_context(config, frozenset(sources))
    _maybe_embeddings(config, ctx, enabled=not no_embed)
    by_source: dict[str, list[VerdictRecord]] = {s: [] for s in sources}

    views = [v for v in ctx.views if _is_sphere_view(v, sphere)]
    if limit:
        views = views[:limit]

    for verdict in _analyze_views(views, ctx, client, config, weights, default_sphere=sphere):
        by_source.setdefault(verdict.source, []).append(verdict)

    for source, verdicts in by_source.items():
        if not verdicts:
            continue
        result = sink.write(source, verdicts, sample_size=sample_size)
        flagged = sum(1 for v in verdicts if v.flags)
        logger.info(
            "%s: analyzed %d, flagged %d, written -> %s (sample: %s)",
            source,
            len(verdicts),
            flagged,
            result.path,
            result.sample_path,
        )


def main() -> None:
    _setup_logging()
    parser = argparse.ArgumentParser(prog="analyze", description="Score corruption risk for a source.")
    parser.add_argument("--source", help="Source id (e.g. ted, caiseop, eop).")
    parser.add_argument("--all", action="store_true", help="Analyze every known source.")
    parser.add_argument(
        "--sphere",
        choices=list(SPHERE_CLI_ALIASES),
        help="Analyze all sources for a sphere (shared context).",
    )
    parser.add_argument("--limit", type=int, default=None, help="Max records to score.")
    parser.add_argument("--sample-size", type=int, default=5, help="Records to write to the sample slice.")
    parser.add_argument("--no-llm", action="store_true", help="Deterministic-only (skip Gemini).")
    parser.add_argument("--no-embed", action="store_true", help="Skip embedding-based similarity.")
    parser.add_argument("--list", action="store_true", help="List known sources and exit.")
    args = parser.parse_args()

    if args.list:
        print("Known sources:", ", ".join(sorted(SOURCE_DEFAULTS)))
        return

    if args.sphere:
        sphere = SPHERE_CLI_ALIASES[args.sphere]
        sources = sorted(sources_for_sphere(sphere))
    else:
        sources = sorted(SOURCE_DEFAULTS) if args.all else ([args.source] if args.source else [])

    if not sources:
        parser.error("Pass --source <id>, --all, --sphere <healthcare|judiciary|police|government|roads>, or --list.")

    config = load_config()
    configure_agent_cap(config.agents_cap)
    configure_eval_budget(config.agents_eval_cap)
    logger.info(
        "Agent governors: AGENTS_CAP=%d concurrent, AGENTS_EVAL_CAP=%s total.",
        config.agents_cap,
        config.agents_eval_cap or "unlimited",
    )
    client = build_client(config) if not args.no_llm else build_client(_DisableLLM(config))
    weights = load_family_weights(config.weights_path)
    sink = VerdictSink(config.verdicts_dir, config.verdict_samples_dir)
    limit = args.limit if args.limit is not None else config.batch_limit

    if args.sphere:
        _analyze_sphere_batch(
            config,
            client,
            weights,
            sink,
            SPHERE_CLI_ALIASES[args.sphere],
            sources,
            limit,
            args.sample_size,
            args.no_embed,
        )
        return

    for source in sources:
        ctx = build_context(config, [source])
        _maybe_embeddings(config, ctx, enabled=not args.no_embed)

        views = ctx.views[:limit] if limit else ctx.views
        verdicts = _analyze_views(views, ctx, client, config, weights)

        result = sink.write(source, verdicts, sample_size=args.sample_size)
        flagged = sum(1 for v in verdicts if v.flags)
        logger.info(
            "%s: analyzed %d, flagged %d, written -> %s (sample: %s)",
            source,
            len(verdicts),
            flagged,
            result.path,
            result.sample_path,
        )


def main_one() -> None:
    _setup_logging()
    parser = argparse.ArgumentParser(prog="analyze-one", description="Score one record -> JSON on stdout.")
    parser.add_argument("--source", help="Source id (for corpus context).")
    parser.add_argument("--natural-key", help="Score the record with this natural_key from the corpus.")
    parser.add_argument("--stdin", action="store_true", help="Read one IngestRecord JSON from stdin.")
    parser.add_argument("--no-llm", action="store_true", help="Deterministic-only (skip Gemini).")
    parser.add_argument(
        "--sphere",
        choices=list(SPHERE_CLI_ALIASES),
        help="Build shared sphere context (e.g. NCPR drug index for healthcare).",
    )
    args = parser.parse_args()

    config = load_config()
    configure_agent_cap(config.agents_cap)
    configure_eval_budget(config.agents_eval_cap)
    client = build_client(config) if not args.no_llm else build_client(_DisableLLM(config))
    weights = load_family_weights(config.weights_path)

    if args.stdin:
        record = json.loads(sys.stdin.read())
        source = record.get("source") or args.source or ""
        if args.sphere:
            ctx = _build_sphere_context(config, sources_for_sphere(SPHERE_CLI_ALIASES[args.sphere]))
        elif source:
            ctx = build_context(config, [source])
        else:
            ctx = context_from_records([record])
        target = view_from_record(record)
        target.corpus_index = None
    else:
        if not (args.source and args.natural_key):
            parser.error("Pass --stdin, or --source <id> --natural-key <key>.")
        if args.sphere:
            ctx = _build_sphere_context(config, sources_for_sphere(SPHERE_CLI_ALIASES[args.sphere]))
        else:
            ctx = build_context(config, [args.source])
        target = next((v for v in ctx.views if v.natural_key == args.natural_key), None)
        if target is None:
            parser.error(f"natural_key '{args.natural_key}' not found in source '{args.source}'.")

    sphere = resolve_sphere(target)
    flow_key = route_flow(target, client, sphere)
    flow = get_flow(flow_key, sphere)
    runnable = _build_agent_runnable(client, ctx, flow.agents)
    verdict = analyze_view(
        target,
        ctx,
        client,
        model_name=config.model,
        weights=weights,
        agent_runnable=runnable,
        flow_key=flow_key,
    )
    print(verdict.model_dump_json(indent=2))


class _DisableLLM:
    """Wrap config to force the NullClient (deterministic-only) path."""

    def __init__(self, config: AnalyzerConfig) -> None:
        self._config = config
        self.has_api_key = False

    def __getattr__(self, name: str):
        return getattr(self._config, name)


if __name__ == "__main__":
    main()
