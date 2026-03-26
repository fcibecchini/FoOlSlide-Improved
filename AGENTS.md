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
- After any code change, run `./scripts/run-tests.sh --with-e2e` before finalizing the response unless the user explicitly asks to skip tests.
- If tests cannot run, report the exact blocker and the attempted command in the final response.

## Commit & Pull Request Guidelines
- Commit messages in this repo follow a lightweight conventional pattern: `feat:`, `fix:`, `refactor:`, `revert:`.
- Keep commits focused and descriptive (one feature or fix per commit where possible).
- PRs should include a clear summary, steps to verify, and screenshots for UI/theme changes.
- Link relevant issues or describe the motivation when there is no issue.

## Development Workflow
- If the requested work is a fix or new feature and the current branch is a clean `main`, first create a feature branch before modifying files; do not wait for the user to ask.
- For fixes and new features, use red/green TDD: add or update a failing automated test first, make the change, then rerun the tests to confirm they pass.
- For fixes and new features, always run manual browser verification with [agent-browser](https://github.com/vercel-labs/agent-browser) before finalizing.
- If a bug is discovered and fixed through browser automation/manual browser testing, add permanent automated coverage for that path as part of the same change.
- Commit new features and fixes on feature branches only; do not commit directly to `main`.
- Use the `gh` CLI to open pull requests targeting the `main` branch.
- Always run the default verification command before finalizing: `./scripts/run-tests.sh --with-e2e`.
- Run e2e smoke tests in addition to the automated test suite when the change affects browser flows or integration behavior.
- Do not attempt remote deployments unless the user explicitly requests them.
- If browser testing is performed locally or remotely, do not mention the specific URLs tested in summaries or PR text.

## Configuration & Security Tips
- Database defaults live in `config.php`; update credentials for non-dev deployments.
- Ensure `content/` subfolders are writable by the web server (`content/cache`, `content/comics`, `content/tags`, `content/logs`).


<!-- BEGIN BEADS INTEGRATION v:1 profile:full hash:f65d5d33 -->
## Issue Tracking with bd (beads)

**IMPORTANT**: This project uses **bd (beads)** for ALL issue tracking. Do NOT use markdown TODOs, task lists, or other tracking methods.

### Why bd?

- Dependency-aware: Track blockers and relationships between issues
- Git-friendly: Dolt-powered version control with native sync
- Agent-optimized: JSON output, ready work detection, discovered-from links
- Prevents duplicate tracking systems and confusion

### Quick Start

**Check for ready work:**

```bash
bd ready --json
```

**Create new issues:**

```bash
bd create "Issue title" --description="Detailed context" -t bug|feature|task -p 0-4 --json
bd create "Issue title" --description="What this issue is about" -p 1 --deps discovered-from:bd-123 --json
```

**Claim and update:**

```bash
bd update <id> --claim --json
bd update bd-42 --priority 1 --json
```

**Complete work:**

```bash
bd close bd-42 --reason "Completed" --json
```

### Issue Types

- `bug` - Something broken
- `feature` - New functionality
- `task` - Work item (tests, docs, refactoring)
- `epic` - Large feature with subtasks
- `chore` - Maintenance (dependencies, tooling)

### Priorities

- `0` - Critical (security, data loss, broken builds)
- `1` - High (major features, important bugs)
- `2` - Medium (default, nice-to-have)
- `3` - Low (polish, optimization)
- `4` - Backlog (future ideas)

### Workflow for AI Agents

1. **Check ready work**: `bd ready` shows unblocked issues
2. **Claim your task atomically**: `bd update <id> --claim`
3. **Work on it**: Implement, test, document
4. **Discover new work?** Create linked issue:
   - `bd create "Found bug" --description="Details about what was found" -p 1 --deps discovered-from:<parent-id>`
5. **Complete**: `bd close <id> --reason "Done"`

### Quality
- Use `--acceptance` and `--design` fields when creating issues
- Use `--validate` to check description completeness

### Lifecycle
- `bd defer <id>` / `bd supersede <id>` for issue management
- `bd stale` / `bd orphans` / `bd lint` for hygiene
- `bd human <id>` to flag for human decisions
- `bd formula list` / `bd mol pour <name>` for structured workflows

### Auto-Sync

bd automatically syncs via Dolt:

- Each write auto-commits to Dolt history
- Use `bd dolt push`/`bd dolt pull` for remote sync
- No manual export/import needed!

### Important Rules

- ✅ Use bd for ALL task tracking
- ✅ Always use `--json` flag for programmatic use
- ✅ Link discovered work with `discovered-from` dependencies
- ✅ Check `bd ready` before asking "what should I work on?"
- ❌ Do NOT create markdown TODO lists
- ❌ Do NOT use external issue trackers
- ❌ Do NOT duplicate tracking systems

For more details, see README.md and docs/QUICKSTART.md.

## Session Completion

**When ending a work session**, you MUST complete ALL steps below. Work is NOT complete until `git push` succeeds.

**MANDATORY WORKFLOW:**

1. **File issues for remaining work** - Create issues for anything that needs follow-up
2. **Run quality gates** (if code changed) - Tests, linters, builds
3. **Update issue status** - Close finished work, update in-progress items
4. **PUSH TO REMOTE** - This is MANDATORY:
   ```bash
   git pull --rebase
   bd dolt push
   git push
   git status  # MUST show "up to date with origin"
   ```
5. **Clean up** - Clear stashes, prune remote branches
6. **Verify** - All changes committed AND pushed
7. **Hand off** - Provide context for next session

**CRITICAL RULES:**
- Work is NOT complete until `git push` succeeds
- NEVER stop before pushing - that leaves work stranded locally
- NEVER say "ready to push when you are" - YOU must push
- If push fails, resolve and retry until it succeeds

<!-- END BEADS INTEGRATION -->
