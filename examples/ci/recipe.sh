#!/usr/bin/env bash
# Runs one example's e2e.sh against this checkout of bank-mails-parser: examples/ci/recipe.sh vub
# The example is copied to a temporary directory first, so the checkout stays untouched.
# Without an argument it runs every example.
set -euo pipefail
examples="$(cd "$(dirname "$0")/.." && pwd)"
if [[ $# -eq 0 ]]; then
    for dir in "$examples"/*/; do
        name="$(basename "$dir")"
        [[ "$name" == ci ]] && continue
        "$0" "$name"
    done
    exit 0
fi
name="$1"
[[ -x "$examples/$name/e2e.sh" ]] || { echo "fail: $examples/$name/e2e.sh not found" >&2; exit 1; }
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cp -R "$examples/$name" "$work/$name"
rm -rf "$work/$name/vendor" "$work/$name/composer.lock"
"$examples/ci/use-local-package.sh" "$work/$name" > "$work/composer.log" 2>&1 \
    || { cat "$work/composer.log" >&2; exit 1; }
"$work/$name/e2e.sh"
