"""Output sinks — write the NDJSON ingest contract + raw provenance snapshots."""

from .ndjson import NdjsonSink, WriteResult

__all__ = ["NdjsonSink", "WriteResult"]
