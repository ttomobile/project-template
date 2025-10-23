APP_DIR := apps/laravel-app

.PHONY: setup lint lint-backend lint-frontend test test-backend test-frontend run audit

setup:
	cd $(APP_DIR) && composer install --no-interaction --prefer-dist
	cd $(APP_DIR) && npm install
	cd $(APP_DIR) && composer run setup

lint: lint-backend lint-frontend

lint-backend:
	cd $(APP_DIR) && composer run lint

lint-frontend:
	cd $(APP_DIR) && npm run lint

test: test-backend test-frontend

test-backend:
	cd $(APP_DIR) && composer run test

test-frontend:
	cd $(APP_DIR) && npm run test

run:
	cd $(APP_DIR) && composer run dev

audit:
	cd $(APP_DIR) && composer run audit
