#!/usr/bin/env bash
set -euo pipefail

ROOT="$(pwd)"
PKG="$ROOT/package"
DEST="$PKG/FILES/assets/sneat"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

SNEAT_COMMIT="40c50e6"
SNEAT_ZIP="$TMP/sneat.zip"
SRC_ROOT="$TMP/src"

echo "[024] Fetching official Sneat v1.0.0 source ($SNEAT_COMMIT)"
curl -fsSL "https://codeload.github.com/themeselection/sneat-bootstrap-html-admin-template-free/zip/$SNEAT_COMMIT" -o "$SNEAT_ZIP"
mkdir -p "$SRC_ROOT"
unzip -q "$SNEAT_ZIP" -d "$SRC_ROOT"
SRC="$(find "$SRC_ROOT" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
test -n "$SRC"

rm -rf "$DEST"
mkdir -p   "$DEST/vendor/css"   "$DEST/vendor/fonts/boxicons"   "$DEST/vendor/js"   "$DEST/vendor/libs/jquery"   "$DEST/vendor/libs/popper"   "$DEST/vendor/libs/perfect-scrollbar"   "$DEST/css"   "$DEST/js"   "$DEST/license"

cp "$SRC/assets/vendor/css/core.css" "$DEST/vendor/css/core.css"
cp "$SRC/assets/vendor/css/theme-default.css" "$DEST/vendor/css/theme-default.css"
cp "$SRC/assets/css/demo.css" "$DEST/css/demo.css"

cp "$SRC/assets/vendor/fonts/boxicons.css" "$DEST/vendor/fonts/boxicons.css"
cp "$SRC"/assets/vendor/fonts/boxicons/boxicons.* "$DEST/vendor/fonts/boxicons/"

cp "$SRC/assets/vendor/js/helpers.js" "$DEST/vendor/js/helpers.js"
cp "$SRC/assets/vendor/js/bootstrap.js" "$DEST/vendor/js/bootstrap.js"
cp "$SRC/assets/vendor/js/menu.js" "$DEST/vendor/js/menu.js"

cp "$SRC/assets/vendor/libs/jquery/jquery.js" "$DEST/vendor/libs/jquery/jquery.js"
cp "$SRC/assets/vendor/libs/popper/popper.js" "$DEST/vendor/libs/popper/popper.js"
cp "$SRC/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" "$DEST/vendor/libs/perfect-scrollbar/perfect-scrollbar.css"
cp "$SRC/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js" "$DEST/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"

cp "$SRC/assets/js/config.js" "$DEST/js/config.js"
cp "$SRC/assets/js/main.js" "$DEST/js/main.js"

if [ -f "$SRC/LICENSE" ]; then cp "$SRC/LICENSE" "$DEST/license/LICENSE"; fi
if [ -f "$SRC/LICENSE.md" ]; then cp "$SRC/LICENSE.md" "$DEST/license/LICENSE.md"; fi
if [ -f "$SRC/CHANGELOG.md" ]; then cp "$SRC/CHANGELOG.md" "$DEST/license/SNEAT_CHANGELOG.md"; fi

echo "Sneat v1.0.0 source commit: $SNEAT_COMMIT" > "$DEST/license/SOURCE.txt"
echo "https://github.com/themeselection/sneat-bootstrap-html-admin-template-free" >> "$DEST/license/SOURCE.txt"

echo "[024] Sneat assets prepared."
