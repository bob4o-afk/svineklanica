# syntax=docker/dockerfile:1
# LOCAL / DEV image: PHP CLI + extensions + composer. docker-compose bind-mounts
# the source and installs deps via `make install`, then runs `artisan serve`.
# The PRODUCTION image is a SEPARATE file — Dockerfile.prod (FrankenPHP).

# ---- base: PHP CLI + extensions + composer ----
FROM php:8.3-cli-alpine AS base
# ca-certificates: required for outbound TLS (Resend mail API, scrapers) — without
# it cURL fails with "unable to get local issuer certificate".
RUN apk add --no-cache ca-certificates git unzip icu-dev postgresql-dev libzip-dev oniguruma-dev linux-headers $PHPIZE_DEPS \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_pgsql intl zip bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# ---- dev: deps installed at runtime via `make install`; source bind-mounted ----
FROM base AS dev
ENV APP_ENV=local
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
