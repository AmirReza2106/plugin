#!/usr/bin/env sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
DIST="$ROOT/dist"
STAGING=$(mktemp -d)
PACKAGE="$STAGING/workshop-registration"

cleanup() {
	rm -rf "$STAGING"
}

trap cleanup EXIT INT TERM

VERSION=$(php -r '$source = file_get_contents($argv[1]); if (1 !== preg_match("/WORKSHOP_REGISTRATION_VERSION[^0-9]+([0-9]+\\.[0-9]+\\.[0-9]+)/", $source, $matches)) { exit(1); } echo $matches[1];' "$ROOT/workshop-registration.php")

mkdir -p "$DIST" "$PACKAGE"
rsync -a \
	--exclude='.git/' \
	--exclude='.gitignore' \
	--exclude='.dockerignore' \
	--exclude='.editorconfig' \
	--exclude='.idea/' \
	--exclude='.opencode/' \
	--exclude='.phpcs-cache' \
	--exclude='.phpstan-cache/' \
	--exclude='.phpunit.cache/' \
	--exclude='.phpunit.result.cache' \
	--exclude='.vscode/' \
	--exclude='assets-src/' \
	--exclude='bin/' \
	--exclude='dist/' \
	--exclude='docker/' \
	--exclude='tests/' \
	--exclude='vendor/' \
	--exclude='.env*' \
	--exclude='compose.yaml' \
	--exclude='Dockerfile' \
	--exclude='phpcs.xml.dist' \
	--exclude='phpstan.neon.dist' \
	--exclude='phpunit.xml.dist' \
	"$ROOT/" "$PACKAGE/"

composer install \
	--working-dir="$PACKAGE" \
	--no-dev \
	--classmap-authoritative \
	--no-interaction \
	--no-progress

rm -f "$PACKAGE/composer.json" "$PACKAGE/composer.lock"

rm -f "$DIST/workshop-registration-$VERSION.zip"
(
	cd "$STAGING"
	zip -q -r "$DIST/workshop-registration-$VERSION.zip" workshop-registration
)

printf '%s\n' "$DIST/workshop-registration-$VERSION.zip"
