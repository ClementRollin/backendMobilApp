DC=docker compose --env-file .env

.PHONY: build up down restart logs ps dexec_web dexec_db composer-install artisan migrate seed migrate-fresh-seed keygen serve

build:
	$(DC) build

up:
	$(DC) up -d

down:
	$(DC) down

restart: down up

logs:
	$(DC) logs -f --tail=200

ps:
	$(DC) ps

dexec_web:
	$(DC) exec web sh

dexec_db:
	$(DC) exec db sh

composer-install:
	$(DC) exec web composer install

artisan:
	$(DC) exec web php artisan $(CMD)

migrate:
	$(DC) exec web php artisan migrate

seed:
	$(DC) exec web php artisan db:seed

migrate-fresh-seed:
	$(DC) exec web php artisan migrate:fresh --seed

keygen:
	$(DC) exec web php artisan key:generate

serve:
	$(DC) exec web php artisan serve --host=0.0.0.0 --port=8000
