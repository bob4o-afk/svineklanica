#!/bin/sh
# Prod entrypoint. Config is cached HERE (at container start), not at build time —
# so the runtime env (.env.prod, injected via env_file) is what gets baked, and a
# new rolling container always picks up the current env. `|| true` keeps a single
# bad cache step from blocking startup (it falls back to uncached, still works).
set -e

php artisan package:discover --ansi || true
php artisan config:cache || true
php artisan route:cache || true
php artisan event:cache || true

# Exec whatever the image/compose asked for (FrankenPHP for `app`,
# `php artisan queue:work` for the queue/scheduler containers).
exec "$@"
