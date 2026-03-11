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

## API domaine taches (Lot 3)

### Contrat JSON
- Succes: `{ "message": "...", "data": ..., "meta"?: ... }`
- Erreur: `{ "message": "...", "errors"?: ... }`
- Suppression tache: HTTP `200` avec `{ "message": "...", "data": null }`

### Endpoints principaux
- `GET /api/tasks`
- `POST /api/tasks`
- `GET /api/tasks/{task}`
- `PUT /api/tasks/{task}` (sans changement de statut)
- `DELETE /api/tasks/{task}`
- `PATCH /api/tasks/{task}/status` (transitions de statut)
- `PATCH /api/tasks/{task}/confirm-blocked`
- `GET /api/tasks/{task}/status-histories`
- `GET/POST /api/tasks/{task}/links`, `DELETE /api/task-links/{taskLink}`
- `GET/POST/PUT/DELETE /api/tags`

### Filtres `GET /api/tasks`
- `scope=visible|created|assigned|unassigned`
- `status`, `priority`, `team_id`, `assignee_id`, `creator_id`
- `due_before`, `due_after` (comparaison inclusive)
- `tag_ids[]` (semantique OR)
- `search` (trim + insensible a la casse, sur `title` et `description`)
- `page`, `per_page` (defaut `15`, max `50`)

### Regles metier importantes
- Les transitions de statut passent uniquement par `PATCH /api/tasks/{task}/status`.
- `PUT /api/tasks/{task}` rejette `status` (422).
- Lead:
  - cree/modifie/supprime dans ses equipes gerees,
  - assigne a un developer de son equipe,
  - peut aussi s'auto-assigner.
- CTO: lecture seule sur les taches.
- `blocked_reason` est requis pour passer une tache en `blocked`.

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
