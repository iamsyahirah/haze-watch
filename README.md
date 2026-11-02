# HazeWatch

Malaysian air quality monitoring and alert system, built with Core PHP and MySQL.

## Requirements

- PHP 8.2 or newer with the PDO MySQL extension for later database milestones
- Composer 2
- MySQL 8 for database-backed milestones

The project intentionally has no PHP framework and no runtime package dependencies. Composer currently provides PSR-4 autoloading.

## Local setup

1. Clone the repository and enter its directory.
2. Copy `.env.example` to `.env` and adjust local values as needed. Never commit `.env`.
3. Run `composer install` to generate the autoloader.
4. Start the local PHP server with `php -S 127.0.0.1:8000 -t public`.
5. Open `http://127.0.0.1:8000`.

The current M1 page is a project shell and does not yet connect to MySQL or display live readings. Database credentials are placeholders until the database milestone.

## Database setup (M2)

Create an empty MySQL 8 database and a least-privilege application user, then set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in `.env` (or in the process environment). Apply the schema and reference data with:

```sh
composer db:migrate
composer db:seed
```

To roll back the most recently applied migration in a development database, run `composer db:rollback`. The initial schema includes users, Malaysian states, provider sources, locations, readings, favourites, alert rules, notifications, and sync logs. The reference seeder adds states and the Haze Monitor source only; station rows wait for a captured provider station response so no locations are fabricated. Migration scripts use MySQL DDL, which performs implicit commits; do not treat a multi-table migration as transactionally reversible if an interruption occurs.

## Project documents

- [Product requirements](prd.md)
- [Technical architecture and API research](docs/architecture.md)

## Configuration

Runtime configuration is read from process environment variables. For local development, the bootstrap also loads a simple `.env` file. Only `public/` should be configured as the web document root. Keep `.env`, `storage/`, `app/`, `bootstrap/`, and `database/` inaccessible from the web.

## Development

Work proceeds one milestone at a time. The external API cache duration, raw measurement units, and exact provider fixture must be verified before implementing ingestion; see the architecture document.
