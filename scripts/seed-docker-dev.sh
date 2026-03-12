#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
SERIES_STUB="${SERIES_STUB:-again-my-childhood-friend}"
SERIES_NAME="${SERIES_NAME:-Again My Childhood Friend}"
SEED_COMIC_UNIQID="${SEED_COMIC_UNIQID:-seedcomic001}"
SEED_CHAPTER_TWO_UNIQID="${SEED_CHAPTER_TWO_UNIQID:-seedchapter002}"
PRIMARY_TYPE_NAME="${PRIMARY_TYPE_NAME:-Manga}"
SECONDARY_TYPE_NAME="${SECONDARY_TYPE_NAME:-Doujinshi}"
PRIMARY_TAG_NAME="${PRIMARY_TAG_NAME:-School Life}"
SECONDARY_TAG_NAME="${SECONDARY_TAG_NAME:-Romance}"
SEED_PAGE1_SOURCE="${SEED_PAGE1_SOURCE:-$ROOT_DIR/scripts/page1.png}"
SEED_PAGE2_SOURCE="${SEED_PAGE2_SOURCE:-$ROOT_DIR/scripts/page2.jpg}"

require_cmd() {
	local cmd="$1"
	if ! command -v "$cmd" >/dev/null 2>&1; then
		echo "[seed] Missing required command: $cmd" >&2
		exit 127
	fi
}

require_cmd docker
require_cmd curl

if [ ! -f "$SEED_PAGE1_SOURCE" ] || [ ! -f "$SEED_PAGE2_SOURCE" ]; then
	echo "[seed] Missing seeded chapter sample pages in scripts/." >&2
	exit 1
fi

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

INSERT INTO fs_typehs (name, description)
SELECT '${PRIMARY_TYPE_NAME}', 'Seed type'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_typehs WHERE name = '${PRIMARY_TYPE_NAME}');

INSERT INTO fs_typehs (name, description)
SELECT '${SECONDARY_TYPE_NAME}', 'Seed type'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_typehs WHERE name = '${SECONDARY_TYPE_NAME}');

INSERT INTO fs_tags (name, description, thumbnail)
SELECT '${PRIMARY_TAG_NAME}', 'Seed tag', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_tags WHERE name = '${PRIMARY_TAG_NAME}');

INSERT INTO fs_tags (name, description, thumbnail)
SELECT '${SECONDARY_TAG_NAME}', 'Seed tag', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_tags WHERE name = '${SECONDARY_TAG_NAME}');

SET @team_id := (SELECT id FROM fs_teams ORDER BY id LIMIT 1);
SET @typeh_id := (SELECT id FROM fs_typehs WHERE name = '${PRIMARY_TYPE_NAME}' LIMIT 1);
SET @secondary_typeh_id := (SELECT id FROM fs_typehs WHERE name = '${SECONDARY_TYPE_NAME}' LIMIT 1);
SET @first_tag_id := (SELECT id FROM fs_tags WHERE name = '${PRIMARY_TAG_NAME}' LIMIT 1);
SET @second_tag_id := (SELECT id FROM fs_tags WHERE name = '${SECONDARY_TAG_NAME}' LIMIT 1);
SET @jointag_id := (
	SELECT jointag_id
	FROM fs_jointags
	WHERE tag_id = @first_tag_id
		AND jointag_id IN (SELECT jointag_id FROM fs_jointags WHERE tag_id = @second_tag_id)
	LIMIT 1
);
SET @next_jointag_id := (SELECT IFNULL(MAX(jointag_id), 0) + 1 FROM fs_jointags);
SET @jointag_id := IFNULL(@jointag_id, @next_jointag_id);

INSERT INTO fs_jointags (jointag_id, tag_id)
SELECT @jointag_id, @first_tag_id
FROM DUAL
WHERE @first_tag_id IS NOT NULL
	AND NOT EXISTS (
		SELECT 1 FROM fs_jointags WHERE jointag_id = @jointag_id AND tag_id = @first_tag_id
	);

INSERT INTO fs_jointags (jointag_id, tag_id)
SELECT @jointag_id, @second_tag_id
FROM DUAL
WHERE @second_tag_id IS NOT NULL
	AND NOT EXISTS (
		SELECT 1 FROM fs_jointags WHERE jointag_id = @jointag_id AND tag_id = @second_tag_id
	);

SET @comic_id := (SELECT id FROM fs_comics WHERE stub = '${SERIES_STUB}' LIMIT 1);

INSERT INTO fs_comics
	(name, stub, uniqid, hidden, author, author_stub, artist, description, parody, parody_stub, urlforum, typeh_id, jointag_id, thumbnail, customchapter, format, adult, created, lastseen, updated, creator, editor)
SELECT
	'${SERIES_NAME}', '${SERIES_STUB}', 'seedcomic001', 0, 'Seed Author', 'seed-author', 'Seed Artist', 'Seed description', '', '', '', @typeh_id, @jointag_id, '', 'Chapter', 0, 1, NOW(), NOW(), NOW(), @admin_user_id, @admin_user_id
FROM DUAL
WHERE @comic_id IS NULL;

SET @comic_id := IFNULL(@comic_id, LAST_INSERT_ID());
UPDATE fs_comics
SET typeh_id = @typeh_id,
	jointag_id = @jointag_id,
	updated = NOW()
WHERE id = @comic_id;

SET @chapter_id := (SELECT id FROM fs_chapters WHERE comic_id = @comic_id AND chapter = 1 AND subchapter = 0 LIMIT 1);
SET @chapter_two_id := (SELECT id FROM fs_chapters WHERE comic_id = @comic_id AND chapter = 2 AND subchapter = 0 LIMIT 1);

INSERT INTO fs_chapters
	(comic_id, team_id, joint_id, chapter, subchapter, volume, language, name, stub, uniqid, hidden, description, thumbnail, created, lastseen, updated, creator, editor, downloads)
SELECT
	@comic_id, @team_id, 0, 1, 0, 1, 'it', 'Seed Chapter', 'seed-chapter', 'seedchapter001', 0, 'Seed chapter', '', NOW(), NOW(), NOW(), @admin_user_id, @admin_user_id, 0
FROM DUAL
WHERE @chapter_id IS NULL;

INSERT INTO fs_chapters
	(comic_id, team_id, joint_id, chapter, subchapter, volume, language, name, stub, uniqid, hidden, description, thumbnail, created, lastseen, updated, creator, editor, downloads)
SELECT
	@comic_id, @team_id, 0, 2, 0, 1, 'it', 'Seed Chapter Two', 'seed-chapter-two', 'seedchapter002', 0, 'Seed chapter two', '', NOW(), NOW(), NOW(), @admin_user_id, @admin_user_id, 0
FROM DUAL
WHERE @chapter_two_id IS NULL;

SET @chapter_two_id := IFNULL(@chapter_two_id, LAST_INSERT_ID());

INSERT INTO fs_preferences (name, value, \`group\`)
SELECT 'fs_dl_enabled', '1', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_preferences WHERE name = 'fs_dl_enabled');

UPDATE fs_preferences
SET value = '1'
WHERE name = 'fs_dl_enabled';

INSERT INTO fs_preferences (name, value, \`group\`)
SELECT 'fs_dl_volume_enabled', '1', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fs_preferences WHERE name = 'fs_dl_volume_enabled');

UPDATE fs_preferences
SET value = '1'
WHERE name = 'fs_dl_volume_enabled';

DELETE FROM fs_pages WHERE chapter_id = @chapter_two_id;

INSERT INTO fs_pages
	(chapter_id, filename, hidden, created, lastseen, updated, creator, editor, height, width, mime, size)
VALUES
	(@chapter_two_id, '001.png', 0, NOW(), CURRENT_TIMESTAMP, NOW(), @admin_user_id, @admin_user_id, 1740, 1247, 'image/png', 1879978),
	(@chapter_two_id, '002.jpg', 0, NOW(), CURRENT_TIMESTAMP, NOW(), @admin_user_id, @admin_user_id, 1073, 736, 'image/jpeg', 132961);
SQL"

docker compose cp "$SEED_PAGE1_SOURCE" web:/tmp/foolslide-seed-page1.png >/dev/null
docker compose cp "$SEED_PAGE2_SOURCE" web:/tmp/foolslide-seed-page2.jpg >/dev/null

docker compose exec -T web sh -lc "
set -e
comic_dir='/var/www/html/content/comics/${SERIES_STUB}_${SEED_COMIC_UNIQID}'
chapter_dir=\"\$comic_dir/seed-chapter-two_${SEED_CHAPTER_TWO_UNIQID}\"
mkdir -p \"\$chapter_dir\"
cp /tmp/foolslide-seed-page1.png \"\$chapter_dir/001.png\"
cp /tmp/foolslide-seed-page2.jpg \"\$chapter_dir/002.jpg\"
chown -R www-data:www-data \"\$comic_dir\"
chmod 0775 \"\$comic_dir\" \"\$chapter_dir\"
chmod 0664 \"\$chapter_dir/001.png\" \"\$chapter_dir/002.jpg\"
rm -f /tmp/foolslide-seed-page1.png /tmp/foolslide-seed-page2.jpg
"

echo "[seed] Ensured Docker dev seed data: ${ADMIN_USER}/${ADMIN_PASSWORD}, ${SERIES_STUB}"
