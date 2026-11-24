#!/usr/bin/env bash
# Runs run.php and compares its output with expected-output.txt.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }
actual="$(mktemp)"
trap 'rm -f "$actual"' EXIT
php run.php > "$actual"
diff -u expected-output.txt "$actual" || { echo "fail: $(basename "$PWD") differs from expected-output.txt" >&2; exit 1; }
echo "ok: $(basename "$PWD") matches expected-output.txt"
