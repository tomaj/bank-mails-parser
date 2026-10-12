#!/usr/bin/env bash
# Build the VitePress site and check links at the real GitHub Pages base.
set -euo pipefail

cd "$(dirname "$0")/.."

port="${DOCS_LINK_PORT:-4173}"
base="/bank-mails-parser/"

npm run docs:build
python3 scripts/check-svg.py .vitepress/dist

if curl -sf -o /dev/null "http://127.0.0.1:${port}${base}"; then
  echo "Port ${port} is already in use" >&2
  exit 1
fi

./node_modules/.bin/vitepress preview --host 127.0.0.1 --port "$port" >/tmp/docs-preview.log 2>&1 &
preview_pid=$!

cleanup() {
  if kill -0 "$preview_pid" 2>/dev/null; then
    kill "$preview_pid" 2>/dev/null || true
    wait "$preview_pid" 2>/dev/null || true
  fi
}
trap cleanup EXIT

ready=0
for _ in $(seq 1 40); do
  if curl -sf -o /dev/null "http://127.0.0.1:${port}${base}"; then
    ready=1
    break
  fi
  if ! kill -0 "$preview_pid" 2>/dev/null; then
    echo "VitePress preview exited before it was ready" >&2
    cat /tmp/docs-preview.log >&2 || true
    exit 1
  fi
  sleep 0.25
done

if [[ "$ready" -ne 1 ]]; then
  echo "VitePress preview did not serve ${base}" >&2
  cat /tmp/docs-preview.log >&2 || true
  exit 1
fi

# The 5.0.0 compare link is the release target. GitHub returns 404 until that tag exists.
skip_args=()
if ! git -C .. ls-remote --tags origin 'refs/tags/5.0.0' | grep -q .; then
  skip_args+=(--skip 'https://github.com/tomaj/bank-mails-parser/compare/4\.1\.0\.\.\.5\.0\.0')
fi

run_check() {
  npx --no-install linkinator "http://127.0.0.1:${port}${base}" \
    --recurse \
    --retry \
    --concurrency 4 \
    --timeout 20000 \
    --verbosity error \
    "${skip_args[@]}"
}

attempt=1
while true; do
  set +e
  run_check 2>&1 | tee /tmp/docs-linkinator.txt
  status=${PIPESTATUS[0]}
  set -e
  if [[ "$status" -eq 0 ]]; then
    break
  fi
  if [[ "$attempt" -ge 4 ]] || ! grep -q '\[429\]' /tmp/docs-linkinator.txt; then
    exit "$status"
  fi
  echo "GitHub rate limited the link check; retrying (${attempt})..." >&2
  attempt=$((attempt + 1))
  sleep $((attempt * 8))
done
