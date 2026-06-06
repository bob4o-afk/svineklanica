#!/bin/sh
# Dev entrypoint — makes `docker compose up` work out of the box on a fresh clone:
# wait for Postgres, install PHP deps if missing, ensure an APP_KEY, then (on the
# `app` container only, RUN_INIT=true) run migrations before handing off to the
# real command. Background workers (queue/scheduler) just wait for the shared,
# bind-mounted vendor/ that the app container installs.
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"

echo "[entrypoint] waiting for ${DB_HOST}:${DB_PORT} ..."
until php -r '$h=getenv("DB_HOST")?:"db"; $p=(int)(getenv("DB_PORT")?:5432); exit(@fsockopen($h,$p)?0:1);' 2>/dev/null; do
  sleep 1
done
echo "[entrypoint] database is up."

if [ "${RUN_INIT:-false}" = "true" ]; then
  if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing — running composer install ..."
    composer install --no-interaction --prefer-dist
  fi
  # Generate a key only if none is set — never clobber an existing APP_KEY.
  if ! grep -qE '^APP_KEY=base64:.+' .env 2>/dev/null; then
    echo "[entrypoint] APP_KEY empty — generating ..."
    php artisan key:generate --force
  fi
  echo "[entrypoint] running migrations ..."
  php artisan migrate --force
else
  # Workers depend on the deps the app container installs into the bind mount.
  until [ -f vendor/autoload.php ]; do
    echo "[entrypoint] waiting for vendor/ (app container installing deps) ..."
    sleep 2
  done
fi

exec "$@"
