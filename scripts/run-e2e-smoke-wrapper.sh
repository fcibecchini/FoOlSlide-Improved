#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [ -n "${CODEX_VIRTUAL_ENV:-}" ]; then
	echo "[e2e-wrapper] CODEX_VIRTUAL_ENV detected; running local smoke tests."
	exec ./scripts/run-e2e-smoke-local.sh "$@"
fi

echo "[e2e-wrapper] CODEX_VIRTUAL_ENV not set; running Docker smoke tests."
exec ./scripts/run-e2e-smoke.sh "$@"
