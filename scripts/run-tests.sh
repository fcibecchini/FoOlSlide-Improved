#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

WITH_E2E=0
for arg in "$@"; do
	case "$arg" in
		--with-e2e)
			WITH_E2E=1
			;;
		-h|--help)
			echo "Usage: ./scripts/run-tests.sh [--with-e2e]"
			echo "  --with-e2e   Also run ./scripts/run-e2e-smoke.sh after unit tests."
			exit 0
			;;
		*)
			echo "[tests] Unknown argument: $arg" >&2
			echo "Usage: ./scripts/run-tests.sh [--with-e2e]" >&2
			exit 2
			;;
	esac
done

PHPUNIT_ARGS=("-c" "phpunit.xml")

run_host_phpunit() {
	if command -v phpunit >/dev/null 2>&1; then
		echo "[tests] Running host phpunit"
		phpunit "${PHPUNIT_ARGS[@]}"
		return $?
	fi

	if [ -x "vendor/bin/phpunit" ]; then
		if ! command -v php >/dev/null 2>&1; then
			echo "[tests] Host php is unavailable, skipping vendor/bin/phpunit and falling back to Docker"
			return 1
		fi

		echo "[tests] Running vendor/bin/phpunit"
		php vendor/bin/phpunit "${PHPUNIT_ARGS[@]}"
		return $?
	fi

	return 1
}

run_docker_phpunit() {
	if ! command -v docker >/dev/null 2>&1; then
		return 125
	fi

	if ! docker compose version >/dev/null 2>&1; then
		return 125
	fi

	echo "[tests] Running phpunit in Docker (service: web)"
	docker compose run --rm -v "$(pwd):/workspace" web sh -lc '
		set -eu
		cd /workspace
		if command -v phpunit >/dev/null 2>&1; then
			phpunit -c phpunit.xml
		elif [ -x vendor/bin/phpunit ]; then
			php vendor/bin/phpunit -c phpunit.xml
		else
			echo "[tests] phpunit is not available in container or vendor/bin/phpunit." >&2
			echo "[tests] Install PHPUnit (host or Composer) and rerun ./scripts/run-tests.sh." >&2
			exit 127
		fi
	'
}

if run_host_phpunit; then
	if [ "$WITH_E2E" -eq 1 ]; then
		echo "[tests] Running e2e smoke suite"
		./scripts/run-e2e-smoke.sh
	fi
	exit 0
fi

docker_status=0
if run_docker_phpunit; then
	if [ "$WITH_E2E" -eq 1 ]; then
		echo "[tests] Running e2e smoke suite"
		./scripts/run-e2e-smoke.sh
	fi
	exit 0
else
	docker_status=$?
fi

if [ "$docker_status" -eq 127 ]; then
	exit 127
fi

if [ "$docker_status" -eq 125 ]; then
	echo "[tests] Unable to run tests." >&2
	echo "[tests] Requirements: phpunit on host, vendor/bin/phpunit, or docker compose with a phpunit-capable web service." >&2
	exit 127
fi

exit "$docker_status"
