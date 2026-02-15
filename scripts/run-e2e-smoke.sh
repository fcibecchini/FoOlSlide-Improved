#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASE_URL="${BASE_URL:-http://localhost:8080}"
START_TIMEOUT_SECONDS="${START_TIMEOUT_SECONDS:-90}"

require_cmd() {
	local cmd="$1"
	if ! command -v "$cmd" >/dev/null 2>&1; then
		echo "[e2e] Missing required command: $cmd" >&2
		exit 127
	fi
}

require_cmd docker
require_cmd curl

if ! docker compose version >/dev/null 2>&1; then
	echo "[e2e] docker compose is required." >&2
	exit 127
fi

echo "[e2e] Starting Docker Compose stack"
docker compose up -d --build

echo "[e2e] Waiting for $BASE_URL to become reachable"
ready=0
for ((i = 1; i <= START_TIMEOUT_SECONDS; i++)); do
	if curl -fsS "$BASE_URL/" >/dev/null 2>&1; then
		ready=1
		break
	fi
	sleep 1
done

if [ "$ready" -ne 1 ]; then
	echo "[e2e] Application did not become ready within ${START_TIMEOUT_SECONDS}s." >&2
	docker compose ps >&2 || true
	docker compose logs --no-color --tail=80 web >&2 || true
	exit 1
fi

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

check_page() {
	local path="$1"
	local min_bytes="$2"
	local marker="${3:-}"

	local safe_name
	safe_name="$(echo "$path" | tr '/:?&=' '_')"
	local headers_file="$tmp_dir/${safe_name}.headers"
	local body_file="$tmp_dir/${safe_name}.body"

	curl -sS -L -D "$headers_file" -o "$body_file" "$BASE_URL$path"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	local content_type
	content_type="$(awk 'BEGIN{IGNORECASE=1} /^Content-Type:/ { val=$0 } END { sub(/\r$/, "", val); print val }' "$headers_file")"
	local body_bytes
	body_bytes="$(wc -c < "$body_file" | tr -d ' ')"

	echo "[e2e] $path -> status=$http_code bytes=$body_bytes"

	if [ "$http_code" != "200" ]; then
		echo "[e2e] FAIL $path: expected HTTP 200, got $http_code" >&2
		exit 1
	fi

	if [ "$body_bytes" -lt "$min_bytes" ]; then
		echo "[e2e] FAIL $path: expected at least $min_bytes bytes, got $body_bytes" >&2
		exit 1
	fi

	if ! grep -qi '<html' "$body_file"; then
		echo "[e2e] FAIL $path: response is not HTML" >&2
		exit 1
	fi

	if [ -n "$marker" ] && ! grep -q "$marker" "$body_file"; then
		echo "[e2e] FAIL $path: marker '$marker' not found" >&2
		exit 1
	fi

	if ! echo "$content_type" | grep -qi 'text/html'; then
		echo "[e2e] FAIL $path: unexpected content type ($content_type)" >&2
		exit 1
	fi
}

# Core public + auth/admin routes that should always render a real HTML page.
check_page "/" 1000 "<!DOCTYPE html"
check_page "/latest/" 1000 "<!DOCTYPE html"
check_page "/account/auth/login/" 1000 "<!DOCTYPE html"
check_page "/admin/" 1000 "<!DOCTYPE html"
check_page "/install" 1000 "<!DOCTYPE html"

echo "[e2e] PASS: smoke routes are serving HTML pages correctly."
