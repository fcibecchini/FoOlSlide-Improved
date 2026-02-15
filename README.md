# FoOlSlide Improved

This is an unofficial continuation of the FoOlSlide reader, focused on maintainability, modern PHP compatibility, and a better admin + reading experience.

Note that this project improved the frontend as well, providing a mobile-friendly version of the reader. The admin panel theme was maninly inspired by the work of chocolatkey's FoOlSlide2.

## Main Features
- Reader and admin panel tailored for manga/doujin archives.
- Comic organization with series metadata, categories, tags, teams, and releases.
- Mobile-friendly frontend theme and improved browsing/navigation flows.
- Migration-based schema updates to keep instances consistent over time.
- Docker-first local development flow for fast setup and reproducible environments.

## Tech Overview
- Framework: CodeIgniter-based legacy application (FoOlSlide stack).
- Runtime data under `content/` (comics, tags, cache, logs).
- Docker services:
  - `web` (PHP + Apache)
  - `db` (MySQL)

## Standard Installation (Server)
1. Copy project files into your web-accessible directory.
2. Ensure writable directories exist and are writable by the web user:
   - `content/cache`
   - `content/comics`
   - `content/tags`
   - `content/logs`
3. Create a MySQL database and user.
4. Open `/install` in your browser (for example: `https://your-domain/install`).
5. Complete the installer with DB credentials and admin user details.
6. Sign in to the admin area and configure site options.

## Run Locally with Docker Compose
To run the full stack locally:

```bash
docker compose up --build
```

Then open:
- `http://localhost:8080/`
- `http://localhost:8080/install` (first-time setup)

Useful lifecycle commands:

```bash
docker compose down
docker compose ps
docker compose logs -f web
```

Notes:
- Named volumes are used for persistent data (`db_data`, `content_*`), so data survives normal `docker compose down`.
- Containers can be rebuilt without losing DB/comics data unless volumes are explicitly removed.

## Testing
- Unit tests are under `tests/controllers/` (`*Test.php`).
- Test bootstrap is in `tests/bootstrap.php`.

Run unit tests:

```bash
./scripts/run-tests.sh
```

Run unit tests + Docker Compose E2E smoke checks:

```bash
./scripts/run-tests.sh --with-e2e
```

The test runner tries, in order:
1. host `phpunit`
2. `vendor/bin/phpunit`
3. Docker (`docker compose run --rm web ...`)
