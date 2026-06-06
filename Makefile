# Convenience targets. Backend + infra run in Docker; the FRONTEND (Vite) runs on the HOST
# (`make web`) for fast native HMR + instant docker restarts — see docker-compose.yml `proxy`.
.PHONY: help build up down restart logs install migrate seed test test-be test-fe lint sh-app web

help:
	@echo "build    - build all images"
	@echo "up       - start the backend stack (api, db, redis, queue, caddy) detached"
	@echo "web      - start the frontend (Vite) on the host — open https://localhost"
	@echo "down     - stop the stack"
	@echo "install  - install backend (composer, in Docker) + frontend (pnpm, on host) deps"
	@echo "migrate  - run DB migrations"
	@echo "seed     - run DB seeders"
	@echo "test     - run backend + frontend test suites"
	@echo "lint     - run pint (backend) + eslint (frontend)"

build:
	docker compose build

up:
	docker compose up -d

# Frontend dev server on the host (native fs watching = real HMR). Caddy proxies
# https://localhost → host.docker.internal:5173, so open https://localhost (NOT :5173).
# (host:true + port:5173 already come from vite.config.ts — `pnpm dev` alone is equivalent.)
web:
	pnpm --dir apps/web dev

down:
	docker compose down

restart: down up

logs:
	docker compose logs -f --tail=100

install:
	docker compose run --rm app composer install
	pnpm --dir apps/web install

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

test: test-be test-fe

test-be:
	-docker compose exec -T db psql -U liberhack -d liberhack -c "CREATE DATABASE liberhack_test" 2>/dev/null || true
	docker compose run --rm -e APP_ENV=testing -e DB_DATABASE=liberhack_test app php artisan test

test-fe:
	pnpm --dir apps/web test -- --run

lint:
	docker compose run --rm app ./vendor/bin/pint --test
	pnpm --dir apps/web lint

sh-app:
	docker compose exec app sh
