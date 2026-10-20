#!/usr/bin/env bash
# Build an installable zip: dist/shilpi-order-stages.zip with root folder "shilpi-order-stages/".
# Usage: bash bin/build.sh
set -euo pipefail

SLUG="shilpi-order-stages"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD="$(mktemp -d)"
trap 'rm -rf "$BUILD"' EXIT

cd "$ROOT"
mkdir -p "$BUILD/$SLUG" dist

# Tracked and new (not ignored) files, minus development files.
git ls-files --cached --others --exclude-standard \
	| grep -Ev '^(\.git/|\.github/|bin/|dist/|node_modules/|vendor/|tests/)' \
	| grep -Ev '(^|/)\.[^/]+$' \
	| grep -Ev '^(phpcs\.xml(\.dist)?|composer\.(json|lock)|package(-lock)?\.json|phpunit\.xml(\.dist)?)$' \
	| while IFS= read -r file; do
		[ -f "$file" ] || continue
		mkdir -p "$BUILD/$SLUG/$(dirname "$file")"
		cp "$file" "$BUILD/$SLUG/$file"
	done

# Fail early on PHP syntax errors when PHP is available.
if command -v php >/dev/null 2>&1; then
	find "$BUILD/$SLUG" -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
fi

rm -f "dist/$SLUG.zip"
(cd "$BUILD" && zip -qr "$ROOT/dist/$SLUG.zip" "$SLUG")
echo "Built dist/$SLUG.zip"
