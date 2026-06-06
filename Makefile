# Convenience targets — everything runs in Docker.
.PHONY: help build up down restart logs install migrate seed test test-be test-fe lint sh-app sh-web

help:
	@echo "build    - build all images"
	@echo "up       - start the stack (detached)"
	@echo "down     - stop the stack"
	@echo "install  - install backend (composer) + frontend (pnpm) deps"
	@echo "migrate  - run DB migrations"
	@echo "seed     - run DB seeders"
	@echo "test     - run backend + frontend test suites"
	@echo "lint     - run pint (backend) + eslint (frontend)"

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

restart: down up

logs:
	docker compose logs -f --tail=100

install:
	docker compose run --rm app composer install
	docker compose run --rm web pnpm install

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

test: test-be test-fe

test-be:
	-docker compose exec -T db psql -U liberhack -d liberhack -c "CREATE DATABASE liberhack_test" 2>/dev/null || true
	docker compose run --rm -e APP_ENV=testing -e DB_DATABASE=liberhack_test app php artisan test

test-fe:
	docker compose run --rm web pnpm test -- --run

lint:
	docker compose run --rm app ./vendor/bin/pint --test
	docker compose run --rm web pnpm lint

sh-app:
	docker compose exec app sh

sh-web:
	docker compose exec web sh
