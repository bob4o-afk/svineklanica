# syntax=docker/dockerfile:1
# Multi-stage: a shared base, a `dev` target (source bind-mounted by compose),
# and a `prod` target that bakes the app + optimized autoloader.

# ---- base: PHP CLI + extensions + composer ----
FROM php:8.3-cli-alpine AS base
RUN apk add --no-cache git unzip icu-dev postgresql-dev libzip-dev oniguruma-dev linux-headers $PHPIZE_DEPS \
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

# ---- prod: full build with optimized autoloader (used by release.yml) ----
FROM base AS prod
ENV APP_ENV=production
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && php artisan config:cache || true
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
