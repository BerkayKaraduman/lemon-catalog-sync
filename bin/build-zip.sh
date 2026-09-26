#!/usr/bin/env bash
# Builds build/lemon-catalog-sync.zip containing only runtime files.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="lemon-catalog-sync"
OUT="$ROOT/build"
STAGE="$OUT/$SLUG"

rm -rf "$OUT"
mkdir -p "$STAGE"

for path in lemon-catalog-sync.php uninstall.php readme.txt LICENSE includes admin elementor public; do
	cp -R "$ROOT/$path" "$STAGE/"
done

find "$STAGE" -name '.DS_Store' -delete

(cd "$OUT" && zip -rqX "$SLUG.zip" "$SLUG")
rm -rf "$STAGE"
echo "Created $OUT/$SLUG.zip"
