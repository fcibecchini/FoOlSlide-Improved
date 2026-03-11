#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
SERIES_STUB="${SERIES_STUB:-again-my-childhood-friend}"
SERIES_NAME="${SERIES_NAME:-Again My Childhood Friend}"

require_cmd() {
	local cmd="$1"
	if ! command -v "$cmd" >/dev/null 2>&1; then
		echo "[seed] Missing required command: $cmd" >&2
		exit 127
	fi
}

require_cmd docker
require_cmd curl

for ((i = 1; i <= 90; i++)); do
	if curl -fsS "$BASE_URL/" >/dev/null 2>&1; then
		break
	fi
	sleep 1
done

# Trigger bootstrap/migrations before seeding.
curl -fsS "$BASE_URL/" >/dev/null

admin_hash="$(
	docker compose exec -T web php -r '
require "/var/www/html/application/libraries/phpass-0.1/PasswordHash.php";
$hasher = new PasswordHash(8, FALSE);
echo $hasher->HashPassword("admin"), PHP_EOL;
' | tr -d '\r\n'
)"

docker compose exec -T db sh -lc "mysql -u foolslide_user -pfoobar -D foolslide_db <<'SQL'
SET @admin_user_id := (SELECT id FROM fs_users WHERE username = '${ADMIN_USER}' LIMIT 1);

UPDATE fs_users
SET password = '${admin_hash}',
	email = 'admin@example.com',
	activated = 1,
	updated = NOW()
WHERE id = @admin_user_id;

INSERT INTO fs_users (username, password, email, activated, banned, last_ip, last_login, created, modified, updated)
SELECT '${ADMIN_USER}', '${admin_hash}', 'admin@example.com', 1, 0, '', '0000-00-00 00:00:00', NOW(), NOW(), NOW()
FROM DUAL
WHERE @admin_user_id IS NULL;

SET @admin_user_id := IFNULL(@admin_user_id, LAST_INSERT_ID());

INSERT INTO fs_profiles (user_id, group_id, display_name, twitter, bio)
SELECT @admin_user_id, 1, 'Administrator', '', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_profiles WHERE user_id = @admin_user_id);

SET @team_id := (SELECT id FROM fs_teams ORDER BY id LIMIT 1);
SET @typeh_id := (SELECT id FROM fs_typehs ORDER BY id LIMIT 1);
SET @comic_id := (SELECT id FROM fs_comics WHERE stub = '${SERIES_STUB}' LIMIT 1);

INSERT INTO fs_comics
	(name, stub, uniqid, hidden, author, author_stub, artist, description, parody, parody_stub, urlforum, typeh_id, jointag_id, thumbnail, customchapter, format, adult, created, lastseen, updated, creator, editor)
SELECT
	'${SERIES_NAME}', '${SERIES_STUB}', 'seedcomic001', 0, 'Seed Author', 'seed-author', 'Seed Artist', 'Seed description', '', '', '', @typeh_id, 0, '', 'Chapter', 0, 1, NOW(), NOW(), NOW(), @admin_user_id, @admin_user_id
FROM DUAL
WHERE @comic_id IS NULL;

SET @comic_id := IFNULL(@comic_id, LAST_INSERT_ID());
SET @chapter_id := (SELECT id FROM fs_chapters WHERE comic_id = @comic_id AND chapter = 1 AND subchapter = 0 LIMIT 1);

INSERT INTO fs_chapters
	(comic_id, team_id, joint_id, chapter, subchapter, volume, language, name, stub, uniqid, hidden, description, thumbnail, created, lastseen, updated, creator, editor, downloads)
SELECT
	@comic_id, @team_id, 0, 1, 0, 1, 'it', 'Seed Chapter', 'seed-chapter', 'seedchapter001', 0, 'Seed chapter', '', NOW(), NOW(), NOW(), @admin_user_id, @admin_user_id, 0
FROM DUAL
WHERE @chapter_id IS NULL;
SQL"

echo "[seed] Ensured Docker dev seed data: ${ADMIN_USER}/${ADMIN_PASSWORD}, ${SERIES_STUB}"
