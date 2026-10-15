.PHONY: help install test test-coverage test-unit test-integration lint format phpstan qa qa-fix clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

update: ## Update dependencies
	composer update

test: ## Run deterministic tests
	composer test

test-coverage: ## Run tests with code coverage
	composer test:coverage

test-unit: ## Run unit tests only
	composer test:unit

test-integration: ## Run live API smoke tests
	composer test:integration

lint: ## Check code style (no fixes)
	composer lint

format: ## Fix code style automatically
	composer format

phpstan: ## Run static analysis
	composer phpstan

qa: ## Run all quality checks (lint, phpstan, tests)
	composer qa

qa-fix: ## Run QA with automatic fixes
	composer qa:fix

clean: ## Clean build artifacts
	rm -rf vendor/
	rm -rf coverage/
	rm -rf .phpunit.cache/
	rm -rf .php-cs-fixer.cache
