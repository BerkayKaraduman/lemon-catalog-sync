#!/usr/bin/env bash
# Builds an installable ZIP containing only runtime files.
#
# Usage: bin/build-zip.sh [zip-name]   (default: lemon-catalog-sync)
# The folder inside the ZIP is always "lemon-catalog-sync" so an upload
# replaces an existing installation instead of creating a second plugin.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="lemon-catalog-sync"
ZIP_NAME="${1:-$SLUG}"
OUT="$ROOT/build"
STAGE="$OUT/$SLUG"

rm -rf "$OUT"
mkdir -p "$STAGE"

for path in lemon-catalog-sync.php uninstall.php readme.txt LICENSE includes admin elementor public; do
	cp -R "$ROOT/$path" "$STAGE/"
done

find "$STAGE" -name '.DS_Store' -delete

(cd "$OUT" && zip -rqX "$ZIP_NAME.zip" "$SLUG")
rm -rf "$STAGE"
echo "Created $OUT/$ZIP_NAME.zip"
