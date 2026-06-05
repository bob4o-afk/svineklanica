# Python scraping layer. Build: docker compose build scraper
# Run:   docker compose run --rm scraper uv run scrape --source <x>
FROM python:3.12-slim

# uv for fast, reproducible installs
COPY --from=ghcr.io/astral-sh/uv:latest /uv /uvx /bin/

WORKDIR /app

# Install deps first (better layer caching)
COPY pyproject.toml ./
RUN uv sync --no-install-project || true

COPY . .
RUN uv sync

# Shared handoff dir with the Laravel app (bind-mounted by compose).
ENV INGEST_OUT_DIR=/data/ingest

# Do NOT auto-run a scrape — scraping is explicit and out-of-band.
CMD ["python", "-c", "print('procurement-scraper: run `uv run scrape --source <x>`. See apps/scraper/README.md')"]
