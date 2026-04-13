#!/usr/bin/env bash

set -euo pipefail

SOURCE_PATH="${BASH_SOURCE[0]}"
while [ -L "$SOURCE_PATH" ]; do
	SOURCE_DIR="$(cd -P "$(dirname "$SOURCE_PATH")" && pwd)"
	SOURCE_PATH="$(readlink "$SOURCE_PATH")"
	[[ "$SOURCE_PATH" != /* ]] && SOURCE_PATH="$SOURCE_DIR/$SOURCE_PATH"
done

PROJECT_ROOT="$(cd -P "$(dirname "$SOURCE_PATH")" && pwd)"
PUBLIC_ROOT="${PUBLIC_ROOT:-/home/sistemeberkat.my/public_html}"
MAINTENANCE_ENABLED=0

cleanup() {
	if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
		php "$PROJECT_ROOT/artisan" up || true
	fi
}

trap cleanup EXIT

echo "[0/8] Enable maintenance mode"
php "$PROJECT_ROOT/artisan" down || true
MAINTENANCE_ENABLED=1

echo "[1/8] Pull latest code"
git -C "$PROJECT_ROOT" pull origin main

echo "[2/8] Install PHP dependencies"
composer --working-dir="$PROJECT_ROOT" install --no-dev --optimize-autoloader

echo "[3/8] Clear Laravel caches"
php "$PROJECT_ROOT/artisan" optimize:clear

echo "[4/8] Run database migrations"
php "$PROJECT_ROOT/artisan" migrate --force

echo "[5/8] Build frontend assets"
npm --prefix "$PROJECT_ROOT" run build

echo "[6/8] Sync public assets to web root"
mkdir -p "$PUBLIC_ROOT"
cp -r "$PROJECT_ROOT/public/"* "$PUBLIC_ROOT/"

if [[ -f "$PUBLIC_ROOT/index.php" ]]; then
	sed -i \
		-e "s#../vendor/autoload.php#../sistemeberkat/vendor/autoload.php#" \
		-e "s#../bootstrap/app.php#../sistemeberkat/bootstrap/app.php#" \
		"$PUBLIC_ROOT/index.php"
fi

echo "[7/8] Fix writable permissions"
chmod -R 777 "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache" "$PROJECT_ROOT/database"

echo "[8/8] Disable maintenance mode"
php "$PROJECT_ROOT/artisan" up
MAINTENANCE_ENABLED=0

echo "Deploy complete"