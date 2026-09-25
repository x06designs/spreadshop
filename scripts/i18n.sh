#!/usr/bin/env bash
# Regenerate the translation files from the source: spreadshop.pot, the .po files merged
# against it, and the compiled .mo, .l10n.php and JavaScript .json files.
#
# With --check this writes nothing to the plugin and fails when regenerating would change a
# file, or when a .po file carries an untranslated or fuzzy string. Source references (#:
# lines) and the POT creation date are not compared: they move on every edit and carry no
# meaning for a translation.
set -euo pipefail

cd "$(dirname "$0")/.."

SRC="spreadshop"
LANGS="$SRC/languages"
WP=(php -d memory_limit=1G vendor/wp-cli/wp-cli/php/boot-fs.php --allow-root --quiet)

CHECK=0
[[ "${1:-}" == "--check" ]] && CHECK=1

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
cp -r "$LANGS/." "$WORK/"

# Only the msgid/msgstr content of a catalogue.
normalize() {
  grep -v -e '^#' -e '^"POT-Creation-Date:' "$1"
}

"${WP[@]}" i18n make-pot "$SRC" "$WORK/new.pot" --slug=spreadshop --domain=spreadshop
# An unchanged catalogue keeps its committed file, so the date does not churn and everything
# compiled from it stays byte-identical.
if ! diff -q <(normalize "$WORK/new.pot") <(normalize "$WORK/spreadshop.pot") >/dev/null; then
  mv "$WORK/new.pot" "$WORK/spreadshop.pot"
else
  rm "$WORK/new.pot"
fi

"${WP[@]}" i18n update-po "$WORK/spreadshop.pot" "$WORK"
# update-po rewrites the header: it drops the file comment and the Language line and stamps
# the current time. The header is the translator's, so the committed one is put back.
for po in "$WORK"/*.po; do
  committed="$LANGS/$(basename "$po")"
  [[ -f "$committed" ]] || continue
  { sed '/^$/q' "$committed"; sed '1,/^$/d' "$po"; } > "$po.tmp"
  mv "$po.tmp" "$po"
done
"${WP[@]}" i18n make-mo "$WORK"
"${WP[@]}" i18n make-php "$WORK"
rm -f "$WORK"/*.json
"${WP[@]}" i18n make-json "$WORK"

FAILED=0

# The .po header is kept by hand (see above), so its version is checked against the plugin's.
VERSION="$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9][^[:space:]]*).*/\1/p' \
  "$SRC/spreadshop.php" | head -1)"
for po in "$LANGS"/*.po; do
  grep -q "^\"Project-Id-Version: .* $VERSION\\\\n\"$" "$po" \
    || { echo "$(basename "$po"): Project-Id-Version does not name version $VERSION."; FAILED=1; }
done

for po in "$WORK"/*.po; do
  # Entries the plugin header produced (name, URI, author) are not translated on purpose.
  UNTRANSLATED="$(awk '
    function msgid() { for (i = 1; i <= NF; i++) if ($i ~ /^msgid /) return $i }
    BEGIN { RS = ""; FS = "\n" }
    NR == 1 { next }
    /#\. (Plugin Name|Plugin URI|Author|Author URI) of the plugin/ { next }
    /#, [^\n]*fuzzy/ { print "  fuzzy: " msgid(); next }
    $NF ~ /^msgstr(\[[0-9]\])? ""$/ { print "  untranslated: " msgid() }
  ' "$po")"
  if [[ -n "$UNTRANSLATED" ]]; then
    echo "$(basename "$po") has strings to translate:"
    echo "$UNTRANSLATED"
    FAILED=1
  fi
done

if [[ "$CHECK" -eq 0 ]]; then
  rm -f "$LANGS"/*.json
  cp -r "$WORK/." "$LANGS/"
  echo "Translation files regenerated in $LANGS."
  exit "$FAILED"
fi

for generated in "$WORK"/*; do
  name="$(basename "$generated")"
  committed="$LANGS/$name"
  if [[ ! -f "$committed" ]]; then
    echo "Missing: $committed"
    FAILED=1
  elif [[ "$name" == *.pot || "$name" == *.po ]]; then
    diff -q <(normalize "$generated") <(normalize "$committed") >/dev/null \
      || { echo "Out of date: $committed"; FAILED=1; }
  else
    cmp -s "$generated" "$committed" || { echo "Out of date: $committed"; FAILED=1; }
  fi
done
for committed in "$LANGS"/*; do
  [[ -e "$WORK/$(basename "$committed")" ]] || { echo "Stale: $committed"; FAILED=1; }
done

if [[ "$FAILED" -ne 0 ]]; then
  echo "Run: composer i18n (then translate what is listed above)."
fi
exit "$FAILED"
