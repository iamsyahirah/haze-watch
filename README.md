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

## Project documents

- [Product requirements](prd.md)
- [Technical architecture and API research](docs/architecture.md)

## Configuration

Runtime configuration is read from process environment variables. For local development, the bootstrap also loads a simple `.env` file. Only `public/` should be configured as the web document root. Keep `.env`, `storage/`, `app/`, `bootstrap/`, and `database/` inaccessible from the web.

## Development

Work proceeds one milestone at a time. The external API cache duration, raw measurement units, and exact provider fixture must be verified before implementing ingestion; see the architecture document.
