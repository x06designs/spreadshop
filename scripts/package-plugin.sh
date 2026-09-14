#!/usr/bin/env bash
# Build the distributable plugin: dist/spreadshop/ and dist/spreadshop-<version>.zip.
#
# The plugin has no runtime dependencies and no build step, so the shipped tree is simply
# the spreadshop/ directory. That is deliberate, and this script's real job is to prove it:
# every dev file lives at the repository root, outside spreadshop/, and the guards below
# fail the build if anything ever leaks in.
set -euo pipefail

cd "$(dirname "$0")/.."

SRC="spreadshop"
OUT="dist/spreadshop"

VERSION="$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9][^[:space:]]*).*/\1/p' \
  "$SRC/spreadshop.php" | head -1)"
[[ -n "$VERSION" ]] || { echo "Could not read Version from the plugin header."; exit 1; }

echo "== packaging spreadshop $VERSION =="

# The version is stated twice: the header WordPress reads, and the constant the code reports
# to Spreadshirt. The unit suite asserts they agree, but packaging must not trust that a test
# was run.
CONST_VERSION="$(sed -nE "s/^[[:space:]]*const SPREADSHOP_VERSION[[:space:]]*=[[:space:]]*'([^']+)'.*/\1/p" \
  "$SRC/includes/Constants.php" | head -1)"
if [[ "$VERSION" != "$CONST_VERSION" ]]; then
  echo "Version drift: header says '$VERSION', Constants says '$CONST_VERSION'."
  exit 1
fi

echo "-- assembling $OUT"
rm -rf "$OUT"
mkdir -p dist
cp -r "$SRC" "$OUT"

# Nothing below belongs in a release. These are guards rather than deletions on purpose: if
# one ever fires, a dev file has moved into the plugin directory and that is worth knowing
# rather than silently stripping.
echo "-- checking for development files"
LEAKED="$(find "$OUT" \
  \( -name 'composer.json' -o -name 'composer.lock' -o -name 'vendor' \
     -o -name 'tests' -o -name 'phpunit.xml*' -o -name 'phpcs.xml*' -o -name 'phpstan.neon*' \
     -o -name 'node_modules' -o -name '.git*' \) -print)"
if [[ -n "$LEAKED" ]]; then
  echo "Development files found in the plugin directory:"
  echo "$LEAKED"
  exit 1
fi

# A missing bootstrap or autoloader produces a zip that fatals on activation, which is the
# one failure mode worth being paranoid about.
for required in "spreadshop.php" "includes/Autoloader.php" "includes/Plugin.php" "templates/embed-page.php"; do
  [[ -f "$OUT/$required" ]] || { echo "Missing from the build: $required"; exit 1; }
done

echo "-- writing dist/spreadshop-$VERSION.zip"
rm -f "dist/spreadshop-$VERSION.zip"
# zip is not installed everywhere this runs; python's zipfile is the portable fallback.
if command -v zip >/dev/null 2>&1; then
  ( cd dist && zip -rq "spreadshop-$VERSION.zip" spreadshop )
elif command -v python3 >/dev/null 2>&1; then
  ( cd dist && python3 -m zipfile -c "spreadshop-$VERSION.zip" spreadshop )
else
  echo "Neither zip nor python3 is available; cannot build the archive."
  exit 1
fi

echo
echo "   tree: $OUT"
echo "   zip:  dist/spreadshop-$VERSION.zip  ($(du -h "dist/spreadshop-$VERSION.zip" | cut -f1))"
echo "   files: $(find "$OUT" -type f | wc -l)"
