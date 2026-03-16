#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASE_URL="${BASE_URL:-http://localhost:8080}"
START_TIMEOUT_SECONDS="${START_TIMEOUT_SECONDS:-90}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-foolslide_db}"
DB_USER="${DB_USER:-foolslide_user}"
DB_PASSWORD="${DB_PASSWORD:-foobar}"
SEED_LOCAL_STATE="${SEED_LOCAL_STATE:-0}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
MAX_HEADER_BYTES="${MAX_HEADER_BYTES:-7000}"
AUTH_REQUIRED="${AUTH_REQUIRED:-0}"
SEEDED_SERIES_STUB="${SEEDED_SERIES_STUB:-again-my-childhood-friend}"
SEEDED_PRIMARY_TAG_NAME="${SEEDED_PRIMARY_TAG_NAME:-School Life}"
SEEDED_DOWNLOAD_PATH="${SEEDED_DOWNLOAD_PATH:-/download/again-my-childhood-friend/seedchapter002/it/1/2/}"
SEEDED_READ_PATH="${SEEDED_READ_PATH:-/read/again-my-childhood-friend/it/1/2/page/1}"

require_cmd() {
	local cmd="$1"
	if ! command -v "$cmd" >/dev/null 2>&1; then
		echo "[e2e] Missing required command: $cmd" >&2
		exit 127
	fi
}

require_cmd curl
require_cmd mysql

db_query() {
	local sql="$1"
	mysql --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "-p$DB_PASSWORD" -D "$DB_NAME" -Nse "$sql" 2>/dev/null | tr -d '\r'
}

current_theme_dir() {
	db_query "SELECT value FROM fs_preferences WHERE name = 'fs_theme_dir' LIMIT 1;"
}

echo "[e2e] Using existing local app runtime at $BASE_URL"
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

check_page_fragment() {
	local path="$1"
	local min_bytes="$2"
	local marker="${3:-}"

	local safe_name
	safe_name="$(echo "fragment_${path}" | tr '/:?&=' '_')"
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

	if [ -n "$marker" ] && ! grep -q "$marker" "$body_file"; then
		echo "[e2e] FAIL $path: marker '$marker' not found" >&2
		exit 1
	fi

	if ! echo "$content_type" | grep -qi 'text/html'; then
		echo "[e2e] FAIL $path: unexpected content type ($content_type)" >&2
		exit 1
	fi
}

check_download_archive() {
	local path="$1"
	local min_bytes="$2"

	local safe_name
	safe_name="$(echo "download_${path}" | tr '/:?&=' '_')"
	local headers_file="$tmp_dir/${safe_name}.headers"
	local body_file="$tmp_dir/${safe_name}.zip"

	curl -sS -L -D "$headers_file" -o "$body_file" "$BASE_URL$path"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	local body_bytes
	body_bytes="$(wc -c < "$body_file" | tr -d ' ')"
	local signature
	signature="$(dd if="$body_file" bs=1 count=2 2>/dev/null)"

	echo "[e2e] DOWNLOAD $path -> status=$http_code bytes=$body_bytes"

	if [ "$http_code" != "200" ]; then
		echo "[e2e] FAIL DOWNLOAD $path: expected HTTP 200, got $http_code" >&2
		exit 1
	fi

	if [ "$body_bytes" -lt "$min_bytes" ]; then
		echo "[e2e] FAIL DOWNLOAD $path: expected at least $min_bytes bytes, got $body_bytes" >&2
		exit 1
	fi

	if [ "$signature" != "PK" ]; then
		echo "[e2e] FAIL DOWNLOAD $path: response is not a ZIP archive" >&2
		exit 1
	fi

	validate_header_size "$headers_file" "$path"
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

check_authed_post_redirect() {
	local path="$1"
	local post_data="$2"
	local expected_location_fragment="$3"

	local safe_name
	safe_name="$(echo "authed_post_${path}_${expected_location_fragment}" | tr '/:?&= ' '_')"
	local headers_file="$tmp_dir/${safe_name}.headers"
	local body_file="$tmp_dir/${safe_name}.body"

	curl -sS -D "$headers_file" -o "$body_file" \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-X POST \
		-H 'Content-Type: application/x-www-form-urlencoded' \
		--data "$post_data" \
		"$BASE_URL$path"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	local location
	location="$(awk 'BEGIN{IGNORECASE=1} /^Location:/ { val=$2 } END { sub(/\r$/, "", val); print val }' "$headers_file")"

	echo "[e2e] AUTH POST $path -> status=$http_code location=$location"

	if [ "$http_code" != "302" ]; then
		echo "[e2e] FAIL AUTH POST $path: expected HTTP 302, got $http_code" >&2
		exit 1
	fi

	if [[ "$location" != *"$expected_location_fragment"* ]]; then
		echo "[e2e] FAIL AUTH POST $path: expected redirect containing '$expected_location_fragment', got '$location'" >&2
		exit 1
	fi

	validate_header_size "$headers_file" "$path"
}

check_authed_redirect() {
	local path="$1"
	local expected_location_fragment="$2"

	local safe_name
	safe_name="$(echo "authed_get_${path}" | tr '/:?&=' '_')"
	local headers_file="$tmp_dir/${safe_name}.headers"
	local body_file="$tmp_dir/${safe_name}.body"

	curl -sS -D "$headers_file" -o "$body_file" \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		"$BASE_URL$path"

	local http_code
	http_code="$(awk '/^HTTP\// { code=$2 } END { print code }' "$headers_file")"
	local location
	location="$(awk 'BEGIN{IGNORECASE=1} /^Location:/ { val=$2 } END { sub(/\r$/, "", val); print val }' "$headers_file")"

	echo "[e2e] AUTH GET $path -> status=$http_code location=$location"

	if [ "$http_code" != "302" ]; then
		echo "[e2e] FAIL AUTH GET $path: expected HTTP 302, got $http_code" >&2
		exit 1
	fi

	if [[ "$location" != *"$expected_location_fragment"* ]]; then
		echo "[e2e] FAIL AUTH GET $path: expected redirect containing '$expected_location_fragment', got '$location'" >&2
		exit 1
	fi
}

create_series_via_admin() {
	local series_name="$1"
	local tag_value="$2"
	local expected_tag_id="${3:-}"
	local data="name=${series_name// /+}&stub=&typeh_id=1&parody=&urlforum=&description=&customchapter=&format=1&author=&artist=&author_stub=&parody_stub=&id=&tags%5B%5D=${tag_value}&tags%5B%5D=0&licensed%5B%5D=&submit=Save"

	curl -sS -o /dev/null \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-X POST \
		-H 'Content-Type: application/x-www-form-urlencoded' \
		--data "$data" \
		"$BASE_URL/admin/series/add_new/"

	local stub
	stub="$(db_query "SELECT stub FROM fs_comics WHERE name = '${series_name}' ORDER BY id DESC LIMIT 1;")"
	if [ -z "$stub" ]; then
		echo "[e2e] FAIL create series '${series_name}': no comic row found." >&2
		exit 1
	fi

	if [ "$stub" = "0" ] || [ "$stub" = "" ]; then
		echo "[e2e] FAIL create series '${series_name}': stub was not generated." >&2
		exit 1
	fi

	if [ -n "$expected_tag_id" ]; then
		local saved_tag_id
		saved_tag_id="$(db_query "SELECT j.tag_id FROM fs_comics c JOIN fs_jointags j ON j.jointag_id = c.jointag_id WHERE c.stub = '${stub}' ORDER BY j.tag_id LIMIT 1;")"
		if [ "$saved_tag_id" != "$expected_tag_id" ]; then
			echo "[e2e] FAIL create series '${series_name}': expected tag_id ${expected_tag_id}, got ${saved_tag_id:-<none>}." >&2
			exit 1
		fi
	fi

	check_authed_page "/admin/series/series/${stub}/" 1000 "${series_name}" >&2
	check_authed_page "/admin/series/add_new/${stub}" 1000 "<!DOCTYPE html" >&2

	echo "$stub"
}

create_team_via_admin() {
	local team_name="$1"
	curl -sS -o /dev/null \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-F "name=${team_name}" \
		-F "url=" \
		-F "forum=" \
		-F "irc=" \
		-F "twitter=" \
		-F "facebook=" \
		-F "facebookid=" \
		-F "id=" \
		"$BASE_URL/admin/members/add_team/"

	local team_stub
	team_stub="$(db_query "SELECT stub FROM fs_teams WHERE name = '${team_name}' ORDER BY id DESC LIMIT 1;")"
	if [ -z "$team_stub" ]; then
		echo "[e2e] FAIL create team '${team_name}': no team row found." >&2
		exit 1
	fi

	if [ "$team_stub" = "0" ] || [ "$team_stub" = "" ]; then
		echo "[e2e] FAIL create team '${team_name}': stub was not generated." >&2
		exit 1
	fi

	check_authed_page "/admin/members/teams/${team_stub}" 1000 "${team_name}" >&2
	check_page_fragment "/team/${team_stub}" 600 "${team_name}" >&2
	check_page "/teamworks/${team_stub}" 800 "<!DOCTYPE html" >&2

	echo "$team_stub"
}

add_team_leader_via_admin() {
	local team_stub="$1"
	local username="$2"
	local user_id
	local team_id

	user_id="$(db_query "SELECT id FROM fs_users WHERE username = '${username}' ORDER BY id DESC LIMIT 1;")"
	team_id="$(db_query "SELECT id FROM fs_teams WHERE stub = '${team_stub}' ORDER BY id DESC LIMIT 1;")"

	if [ -z "$user_id" ] || [ -z "$team_id" ]; then
		echo "[e2e] FAIL add team leader: missing team_id or user_id for ${team_stub}/${username}." >&2
		exit 1
	fi

	curl -sS -o /dev/null \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-X POST \
		-H 'Content-Type: application/x-www-form-urlencoded' \
		--data "username=${username}" \
		"$BASE_URL/admin/members/make_team_leader_username/${team_id}"

	local membership_count
	membership_count="$(db_query "SELECT COUNT(*) FROM fs_memberships WHERE team_id = ${team_id} AND user_id = ${user_id} AND accepted = 1 AND is_leader = 1;")"
	if [ "$membership_count" != "1" ]; then
		echo "[e2e] FAIL add team leader: expected accepted leader membership for ${username} in ${team_stub}." >&2
		exit 1
	fi

	check_authed_page "/admin/members/teams/${team_stub}" 1000 "${username}" >&2
	check_page_fragment "/team/${team_stub}" 600 "teamworks/${team_stub}" >&2
}

create_chapter_via_admin() {
	local series_stub="$1"
	local chapter_number="$2"
	local chapter_name="$3"
	local comic_id
	comic_id="$(db_query "SELECT id FROM fs_comics WHERE stub = '${series_stub}' ORDER BY id DESC LIMIT 1;")"
	if [ -z "$comic_id" ]; then
		echo "[e2e] FAIL create chapter for '${series_stub}': no comic row found." >&2
		exit 1
	fi

	local data="comic_id=${comic_id}&name=${chapter_name// /+}&team%5B%5D=Anonymous&team%5B%5D=&volume=1&chapter=${chapter_number}&subchapter=0&language=it&hidden=0&description=&submit=Save"

	curl -sS -o /dev/null \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-X POST \
		-H 'Content-Type: application/x-www-form-urlencoded' \
		--data "$data" \
		"$BASE_URL/admin/series/add_new/${series_stub}"

	local chapter_id
	chapter_id="$(db_query "SELECT ch.id FROM fs_chapters ch JOIN fs_comics c ON c.id = ch.comic_id WHERE c.stub = '${series_stub}' AND ch.chapter = ${chapter_number} ORDER BY ch.id DESC LIMIT 1;")"
	if [ -z "$chapter_id" ]; then
		echo "[e2e] FAIL create chapter for '${series_stub}': no chapter row found." >&2
		exit 1
	fi

	check_authed_page "/admin/series/series/${series_stub}/${chapter_id}/" 1000 "${chapter_name}" >&2

	echo "$chapter_id"
}

upload_page_via_admin() {
	local chapter_id="$1"
	local response_file="$tmp_dir/upload_${chapter_id}.json"

	curl -sS -o "$response_file" \
		-c "$COOKIE_JAR" -b "$COOKIE_JAR" \
		-F "chapter_id=${chapter_id}" \
		-F "Filedata[]=@${ROOT_DIR}/scripts/page1.png;type=image/png" \
		"$BASE_URL/admin/series/upload"

	if ! grep -q '"name"' "$response_file"; then
		echo "[e2e] FAIL upload for chapter ${chapter_id}: upload endpoint did not return file metadata." >&2
		cat "$response_file" >&2
		exit 1
	fi
}

check_search_tags_multi() {
	local tag_ids
	tag_ids="$(db_query "SELECT id FROM fs_tags ORDER BY id LIMIT 2" | tr '\n' ' ' | xargs || true)"

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

seed_local_dev_state() {
	if [ "$SEED_LOCAL_STATE" != "1" ]; then
		echo "[e2e] SKIP local seeding (set SEED_LOCAL_STATE=1 to enable scripts/seed-local-dev.sh)."
		return 0
	fi

	if [ ! -x "$ROOT_DIR/scripts/seed-local-dev.sh" ]; then
		echo "[e2e] FAIL local seeding requested but scripts/seed-local-dev.sh is missing or not executable." >&2
		exit 1
	fi

	echo "[e2e] Seeding local dev state"
	"$ROOT_DIR/scripts/seed-local-dev.sh"
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
	seed_local_dev_state
	series_marker='id="tablelist"'
	if [ "$(current_theme_dir)" = "dazen-skin" ]; then
		series_marker="comic-hero--no-cover"
	fi
	check_page "/latest/" 1000 "<!DOCTYPE html"
	check_page "/tags/" 1000 "${SEEDED_PRIMARY_TAG_NAME}"
	check_post_page "/series/${SEEDED_SERIES_STUB}/" "adult=true" 1000 "${series_marker}"
	check_page "/about/" 1000 'name="contact_name"'
	check_post_page "/about/" "contact_name=&contact_email=&contact_subject=&contact_message=&contact_website=" 1000 'contact_name'
	check_page "/account/auth/login/" 1000 'name="login"'
	check_page "/admin/" 1000 "<!DOCTYPE html"
	check_page "/install" 1000 "<!DOCTYPE html"
	check_post_page "/search/" "search=aaa%2Ftest%2Fnaruto" 800 "<!DOCTYPE html"
	check_post_page "/search_tags/" "search=invalid_payload" 800 "<!DOCTYPE html"
	check_search_tags_multi
	check_page "$SEEDED_READ_PATH" 1000 "<!DOCTYPE html"
	check_download_archive "$SEEDED_DOWNLOAD_PATH" 1000
	check_page "$SEEDED_READ_PATH" 1000 "<!DOCTYPE html"
	if admin_login; then
		check_authed_page "/admin/series/add_new/" 1000 "<!DOCTYPE html"
		check_authed_page "/admin/series/series/${SEEDED_SERIES_STUB}/" 1000 "Seed Chapter Two"

		first_stub="$(db_query "SELECT stub FROM fs_comics ORDER BY id LIMIT 1;" | head -n 1 || true)"
		if [ -n "$first_stub" ]; then
			check_authed_page "/admin/series/add_new/${first_stub}" 1000 "<!DOCTYPE html"
		else
			echo "[e2e] SKIP /admin/series/add_new/<stub>: no existing series found in local DB."
		fi

		smoke_suffix="$(date +%s)"
		check_authed_page "/admin/members/teams/" 1000 "<!DOCTYPE html"
		seeded_team_stub="$(db_query "SELECT stub FROM fs_teams ORDER BY id LIMIT 1;")"
		if [ -n "$seeded_team_stub" ]; then
			check_authed_redirect "/admin/members/home_team/" "/admin/members/teams/${seeded_team_stub}"
			check_authed_page "/admin/members/teams/${seeded_team_stub}" 1000 "<!DOCTYPE html"
			check_page_fragment "/team/${seeded_team_stub}" 600 "teamworks/${seeded_team_stub}"
		fi
		team_stub="$(create_team_via_admin "Smoke Team ${smoke_suffix}")"
		add_team_leader_via_admin "$team_stub" "$ADMIN_USER"
		no_tag_stub="$(create_series_via_admin "Smoke Series No Tag ${smoke_suffix}" 0)"
		expected_tag_id="$(db_query "SELECT id FROM fs_tags ORDER BY name ASC LIMIT 1 OFFSET 1;")"
		tagged_stub="$(create_series_via_admin "Smoke Series Tagged ${smoke_suffix}" 2 "$expected_tag_id")"
		seeded_upload_chapter_id="$(db_query "SELECT ch.id FROM fs_chapters ch JOIN fs_comics c ON c.id = ch.comic_id WHERE c.stub = '${SEEDED_SERIES_STUB}' AND ch.uniqid = 'seedchapter002' LIMIT 1;")"
		if [ -z "$seeded_upload_chapter_id" ]; then
			echo "[e2e] FAIL upload smoke: no seeded chapter found." >&2
			exit 1
		fi
		upload_page_via_admin "$seeded_upload_chapter_id"

		check_authed_page "/admin/series/series/${tagged_stub}/" 1000 "Smoke Series Tagged ${smoke_suffix}"
	fi
fi

echo "[e2e] PASS: smoke routes are serving HTML pages correctly."
