#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

POT_FILE="assets/locale/default.pot"

if ! command -v msgcmp >/dev/null 2>&1 || ! command -v msgfmt >/dev/null 2>&1; then
	echo "[locales] msgcmp and msgfmt are required." >&2
	exit 127
fi

status=0

for po in assets/locale/*.utf8/LC_MESSAGES/*.po; do
	echo "[locales] Checking ${po}"

	if ! msgcmp --use-untranslated "$po" "$POT_FILE" >/dev/null 2>&1; then
		echo "[locales] ${po} is out of sync with ${POT_FILE}." >&2
		status=1
	fi

	stats="$(msgfmt --statistics -o /dev/null "$po" 2>&1 || true)"
	if printf '%s\n' "$stats" | grep -Eq 'untranslated|fuzzy'; then
		echo "[locales] ${po} has incomplete translations: ${stats}" >&2
		status=1
	fi
done

exit "$status"
