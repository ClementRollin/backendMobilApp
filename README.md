# Backend TaskCollab (Laravel + Docker + Make)

## Prerequis
- Docker Desktop demarre
- GNU Make

## Setup rapide
Depuis `backend/`:

```bash
cp .env.example .env
make build
make up
make dexec_web
```

Dans le shell `web`:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
```

Dans un autre terminal (toujours dans `backend/`):

```bash
make serve
```

API: `http://localhost:8000/api`

## Cibles Make
- `make build`
- `make up`
- `make down`
- `make restart`
- `make logs`
- `make ps`
- `make dexec_web`
- `make dexec_db`
- `make composer-install`
- `make artisan CMD="migrate"`
- `make migrate`
- `make seed`
- `make migrate-fresh-seed`
- `make keygen`
- `make serve`

## Secrets et variables
Les variables sensibles ne sont pas en dur dans `docker-compose.yml`.
Elles sont lues depuis **`backend/.env`**:

```env
DB_DATABASE=task_collab
DB_USERNAME=taskcollab
DB_PASSWORD=change_me
DB_FORWARD_PORT=5433
WEB_PORT=8000
COMPOSE_PROJECT_NAME=taskcollab
```

## DBeaver (DB Docker)
- Host: `127.0.0.1`
- Port: valeur de `DB_FORWARD_PORT` (defaut `5433`)
- Database: `DB_DATABASE`
- Username: `DB_USERNAME`
- Password: `DB_PASSWORD`
