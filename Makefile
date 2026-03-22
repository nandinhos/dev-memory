.PHONY: help build up down restart logs logs-app logs-postgres logs-redis shell migrate seed test clean

# Variables
APP_NAME := dev-memory
APP_PORT ?= 8000

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build Docker images
	docker compose build --no-cache

up: ## Start containers
	docker compose up -d
	@echo "App running at http://localhost:$(APP_PORT)"

down: ## Stop containers
	docker compose down

restart: down up ## Restart containers

logs: ## Show all logs
	docker compose logs -f

logs-app: ## Show app logs
	docker compose logs -f app

logs-postgres: ## Show PostgreSQL logs
	docker compose logs -f postgres

logs-redis: ## Show Redis logs
	docker compose logs -f redis

shell: ## Shell into app container
	docker compose exec app bash

migrate: ## Run migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Fresh migrate with seed
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Seed database
	docker compose exec app php artisan db:seed

test: ## Run tests
	docker compose exec app php artisan test

test-unit: ## Run unit tests
	docker compose exec app ./vendor/bin/phpunit --testsuite=Unit

test-feature: ## Run feature tests
	docker compose exec app ./vendor/bin/phpunit --testsuite=Feature

cache-clear: ## Clear all caches
	docker compose exec app php artisan optimize:clear

optimize: ## Optimize for production
	docker compose exec app php artisan optimize

composer-install: ## Install composer dependencies
	docker compose exec app composer install

npm-install: ## Install npm dependencies
	docker compose exec app npm install

npm-build: ## Build assets
	docker compose exec app npm run build

clean: ## Remove containers, volumes and images
	docker compose down -v --remove-orphans
	docker image rm $(APP_NAME) || true

rebuild: clean build up ## Full rebuild
