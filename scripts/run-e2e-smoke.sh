#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASE_URL="${BASE_URL:-http://localhost:8080}"
START_TIMEOUT_SECONDS="${START_TIMEOUT_SECONDS:-90}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
MAX_HEADER_BYTES="${MAX_HEADER_BYTES:-7000}"
AUTH_REQUIRED="${AUTH_REQUIRED:-0}"
SEEDED_SERIES_STUB="${SEEDED_SERIES_STUB:-again-my-childhood-friend}"

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
COOKIE_JAR="$tmp_dir/cookies.txt"

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

check_post_page() {
	local path="$1"
	local post_data="$2"
	local min_bytes="$3"
	local marker="${4:-}"

	local safe_name
	safe_name="$(echo "post_${path}_${post_data}" | tr '/:?&= ' '_')"
	local headers_file="$tmp_dir/${safe_name}.headers"
	local body_file="$tmp_dir/${safe_name}.body"

	curl -sS -L -D "$headers_file" -o "$body_file" \
		-X POST \
		-H 'Content-Type: application/x-www-form-urlencoded' \
		--data "$post_data" \
		"$BASE_URL$path"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	local content_type
	content_type="$(awk 'BEGIN{IGNORECASE=1} /^Content-Type:/ { val=$0 } END { sub(/\r$/, "", val); print val }' "$headers_file")"
	local body_bytes
	body_bytes="$(wc -c < "$body_file" | tr -d ' ')"

	echo "[e2e] POST $path -> status=$http_code bytes=$body_bytes"

	if [ "$http_code" != "200" ]; then
		echo "[e2e] FAIL POST $path: expected HTTP 200, got $http_code" >&2
		exit 1
	fi

	if [ "$body_bytes" -lt "$min_bytes" ]; then
		echo "[e2e] FAIL POST $path: expected at least $min_bytes bytes, got $body_bytes" >&2
		exit 1
	fi

	if ! grep -qi '<html' "$body_file"; then
		echo "[e2e] FAIL POST $path: response is not HTML" >&2
		exit 1
	fi

	if [ -n "$marker" ] && ! grep -q "$marker" "$body_file"; then
		echo "[e2e] FAIL POST $path: marker '$marker' not found" >&2
		exit 1
	fi

	if ! echo "$content_type" | grep -qi 'text/html'; then
		echo "[e2e] FAIL POST $path: unexpected content type ($content_type)" >&2
		exit 1
	fi
}

validate_header_size() {
	local headers_file="$1"
	local path="$2"
	local header_bytes
	header_bytes="$(wc -c < "$headers_file" | tr -d ' ')"
	echo "[e2e] $path headers_bytes=$header_bytes"
	if [ "$header_bytes" -gt "$MAX_HEADER_BYTES" ]; then
		echo "[e2e] FAIL $path: response headers too large ($header_bytes > $MAX_HEADER_BYTES)." >&2
		exit 1
	fi
}

admin_login() {
	local headers_file="$tmp_dir/admin_login.headers"
	local body_file="$tmp_dir/admin_login.body"

	curl -sS -L -D "$headers_file" -o "$body_file" \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-X POST \
		-H 'Content-Type: application/x-www-form-urlencoded' \
		--data "login=${ADMIN_USER}&password=${ADMIN_PASSWORD}&remember=1&submit=Login" \
		"$BASE_URL/account/auth/login/"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	echo "[e2e] POST /account/auth/login/ -> status=$http_code"

	if [ "$http_code" != "200" ]; then
		if [ "$AUTH_REQUIRED" = "1" ]; then
			echo "[e2e] FAIL login: expected HTTP 200 after redirects, got $http_code" >&2
			exit 1
		fi
		echo "[e2e] SKIP auth checks: login flow unavailable (status=$http_code)."
		return 1
	fi

	if grep -q "Incorrect password" "$body_file"; then
		if [ "$AUTH_REQUIRED" = "1" ]; then
			echo "[e2e] FAIL login: invalid credentials for admin flow checks." >&2
			exit 1
		fi
		echo "[e2e] SKIP auth checks: invalid admin credentials for this environment."
		return 1
	fi

	if ! grep -q "/account/auth/logout/" "$body_file"; then
		if [ "$AUTH_REQUIRED" = "1" ]; then
			echo "[e2e] FAIL login: expected authenticated profile page with logout link." >&2
			exit 1
		fi
		echo "[e2e] SKIP auth checks: authenticated marker not found after login."
		return 1
	fi

	validate_header_size "$headers_file" "/account/auth/login/"
	return 0
}

check_authed_page() {
	local path="$1"
	local min_bytes="$2"
	local marker="${3:-}"

	local safe_name
	safe_name="$(echo "authed_${path}" | tr '/:?&=' '_')"
	local headers_file="$tmp_dir/${safe_name}.headers"
	local body_file="$tmp_dir/${safe_name}.body"

	curl -sS -L -D "$headers_file" -o "$body_file" \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		"$BASE_URL$path"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	local body_bytes
	body_bytes="$(wc -c < "$body_file" | tr -d ' ')"

	echo "[e2e] AUTH $path -> status=$http_code bytes=$body_bytes"

	if [ "$http_code" != "200" ]; then
		echo "[e2e] FAIL AUTH $path: expected HTTP 200, got $http_code" >&2
		exit 1
	fi

	if [ "$body_bytes" -lt "$min_bytes" ]; then
		echo "[e2e] FAIL AUTH $path: expected at least $min_bytes bytes, got $body_bytes" >&2
		exit 1
	fi

	if [ -n "$marker" ] && ! grep -q "$marker" "$body_file"; then
		echo "[e2e] FAIL AUTH $path: marker '$marker' not found" >&2
		exit 1
	fi

	validate_header_size "$headers_file" "$path"
}

check_search_tags_multi() {
	local tag_ids
	tag_ids="$(docker compose exec -T db sh -lc "mysql -u foolslide_user -pfoobar -D foolslide_db -Nse \"SELECT id FROM fs_tags ORDER BY id LIMIT 2\"" 2>/dev/null | tr '\n' ' ' | xargs || true)"

	if [ -z "$tag_ids" ]; then
		echo "[e2e] SKIP POST /search_tags/ multi-tag check: no tags found in local DB."
		return 0
	fi

	local first second
	first="$(echo "$tag_ids" | awk '{print $1}')"
	second="$(echo "$tag_ids" | awk '{print $2}')"

	if [ -z "$first" ] || [ -z "$second" ]; then
		echo "[e2e] SKIP POST /search_tags/ multi-tag check: need at least two tags in local DB."
		return 0
	fi

	check_post_page "/search_tags/" "tag%5B%5D=${first}&tag%5B%5D=${second}" 800 "<!DOCTYPE html"
}

detect_install_state() {
	local headers_file="$tmp_dir/install_state.headers"
	local body_file="$tmp_dir/install_state.body"

	curl -sS -L -D "$headers_file" -o "$body_file" "$BASE_URL/"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	if [ "$http_code" != "200" ]; then
		echo "[e2e] FAIL install-state probe: expected HTTP 200, got $http_code" >&2
		exit 1
	fi

	if grep -q 'Installing FoOlSlide' "$body_file"; then
		return 0
	fi

	return 1
}

seed_docker_dev_state() {
	echo "[e2e] Seeding Docker dev state"
	"$ROOT_DIR/scripts/seed-docker-dev.sh"
}

# Core public + auth/admin routes that should always render a real HTML page.
check_page "/" 1000 "<!DOCTYPE html"
if detect_install_state; then
	echo "[e2e] Detected installer state; validating install pages."
	check_page "/latest/" 1000 "Installing FoOlSlide"
	check_page "/account/auth/login/" 1000 "Installing FoOlSlide"
	check_page "/admin/" 1000 "Installing FoOlSlide"
	check_page "/install" 1000 "Installing FoOlSlide"
else
	seed_docker_dev_state
	check_page "/latest/" 1000 "<!DOCTYPE html"
	check_page "/account/auth/login/" 1000 'name="login"'
	check_page "/admin/" 1000 "<!DOCTYPE html"
	check_page "/install" 1000 "<!DOCTYPE html"
	check_post_page "/search/" "search=aaa%2Ftest%2Fnaruto" 800 "<!DOCTYPE html"
	check_post_page "/search_tags/" "search=invalid_payload" 800 "<!DOCTYPE html"
	check_search_tags_multi
	if admin_login; then
		check_authed_page "/admin/series/add_new/" 1000 "<!DOCTYPE html"
		check_authed_page "/admin/series/series/${SEEDED_SERIES_STUB}/" 1000 "<!DOCTYPE html"

		first_stub="$(docker compose exec -T db sh -lc "mysql -u foolslide_user -pfoobar -D foolslide_db -Nse \"SELECT stub FROM fs_comics ORDER BY id LIMIT 1\"" 2>/dev/null | tr -d '\r' | head -n 1 || true)"
		if [ -n "$first_stub" ]; then
			check_authed_page "/admin/series/add_new/${first_stub}" 1000 "<!DOCTYPE html"
		else
			echo "[e2e] SKIP /admin/series/add_new/<stub>: no existing series found in local DB."
		fi
	fi
fi

echo "[e2e] PASS: smoke routes are serving HTML pages correctly."
