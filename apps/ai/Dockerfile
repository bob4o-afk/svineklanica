# AI corruption-detector layer. Build context is ./apps (so the editable
# `procurement-scraper` path dependency at ../scraper resolves).
#   docker compose build ai
#   docker compose run --rm ai uv run analyze --source ted
FROM python:3.12-slim

# uv for fast, reproducible installs
COPY --from=ghcr.io/astral-sh/uv:latest /uv /uvx /bin/

WORKDIR /app/ai

# The analyzer depends (editable) on the scraper package; both must be present.
COPY scraper /app/scraper
COPY ai /app/ai

RUN uv sync

# Shared handoff dir with the Laravel app (bind-mounted by compose).
ENV INGEST_OUT_DIR=/data/ingest

# Do NOT auto-run — analysis is explicit and triggered on demand (control panel / CLI).
CMD ["python", "-c", "print('procurement-analyzer: run `uv run analyze --source <x>`. See apps/ai/README.md')"]
