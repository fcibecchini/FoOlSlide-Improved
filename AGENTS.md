# Repository Guidelines

## Project Structure & Module Organization
- `application/` contains the CodeIgniter app: controllers, models, views, config, and libraries.
- `system/` holds the CodeIgniter framework core.
- `content/` stores runtime data (comics, tags, cache, logs) and must be writable in production.
- `content/themes/` contains reader/admin themes and assets for UI changes.
- `assets/` includes third-party libraries (e.g., HTMLPurifier) and static resources.
- Entry points live in `index.php` and `application/controllers/` (e.g., `reader.php`, `install.php`).

## Build, Test, and Development Commands
- `docker compose up --build` builds the PHP/Apache image and starts the app plus MySQL (see `docker-compose.yml`).
- `docker compose down` stops containers and preserves named volumes (database + content storage).
- Local install flow: after the web server is running, open `/install` to create the database and admin user.

## Coding Style & Naming Conventions
- PHP code follows the existing CodeIgniter style in this repo: tabs for indentation and braces on new lines.
- Controller classes use PascalCase (e.g., `Content`, `Reader`), methods use lower camelCase.
- Keep config changes in `application/config/` or `config.php`, and avoid hardcoding paths.
- No automated formatter is configured; keep diffs small and match nearby style.

## Testing Guidelines
- Unit tests use PHPUnit with `phpunit.xml` at the repository root.
- Test files live under `tests/controllers/` and use the `*Test.php` suffix.
- Run tests with `./scripts/run-tests.sh` (preferred).
- `./scripts/run-tests.sh` automatically tries host `phpunit`, `vendor/bin/phpunit`, then Docker (`web` service) with a bind mount.
- Validate major behavior changes manually by running the app and checking reader/admin flows.
- Run end-to-end smoke tests for browser-visible changes to confirm the main flows still work.

## Agent Verification Rule
- After any code change, run `./scripts/run-tests.sh` before finalizing the response unless the user explicitly asks to skip tests.
- If tests cannot run, report the exact blocker and the attempted command in the final response.

## Commit & Pull Request Guidelines
- Commit messages in this repo follow a lightweight conventional pattern: `feat:`, `fix:`, `refactor:`, `revert:`.
- Keep commits focused and descriptive (one feature or fix per commit where possible).
- PRs should include a clear summary, steps to verify, and screenshots for UI/theme changes.
- Link relevant issues or describe the motivation when there is no issue.

## Development Workflow
- Commit new features and fixes on feature branches only; do not commit directly to `docker`.
- Use the `gh` CLI to open pull requests targeting the `docker` branch.
- Always run the full test suite with `./scripts/run-tests.sh` to verify work before finalizing.
- Run e2e smoke tests in addition to the automated test suite when the change affects browser flows or integration behavior.
- Do not attempt remote deployments unless the user explicitly requests them.
- If browser testing is performed locally or remotely, do not mention the specific URLs tested in summaries or PR text.

## Configuration & Security Tips
- Database defaults live in `config.php`; update credentials for non-dev deployments.
- Ensure `content/` subfolders are writable by the web server (`content/cache`, `content/comics`, `content/tags`, `content/logs`).
