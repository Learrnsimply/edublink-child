#!/usr/bin/env bash
# Local PHP syntax lint for the edublink-child theme — no system PHP needed (uses Docker).
# Matches the production PHP major version (8.x) closely enough for syntax (-l) checks.
# Usage:
#   ./php-lint.sh functions.php front-page.php      # lint specific files (paths relative to website/)
#   ./php-lint.sh --all                             # lint every theme PHP (excl. vendor/)
set -euo pipefail

IMG="php:8.2-cli-alpine"
ROOT="$(cd "$(dirname "$0")/../website" && pwd)"

lint() {
  docker run --rm -v "$ROOT":/app "$IMG" php -l "/app/$1"
}

if [ "${1:-}" = "--all" ]; then
  cd "$ROOT"
  fail=0
  while IFS= read -r -d '' f; do
    lint "${f#./}" || fail=1
  done < <(find . -name '*.php' -not -path './vendor/*' -print0)
  exit "$fail"
else
  [ "$#" -ge 1 ] || { echo "usage: $0 <file.php> [...]  |  $0 --all"; exit 2; }
  for f in "$@"; do lint "$f"; done
fi
