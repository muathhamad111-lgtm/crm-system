#!/usr/bin/env bash
#
# Deploy script for crm.moathhamad.space
# Run on the server inside the app directory (or via the GitHub Actions SSH step).
# Usage: bash deploy/deploy.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

# Allow running composer as root inside CI without the interactive warning.
export COMPOSER_ALLOW_SUPERUSER=1
WEB_USER="${WEB_USER:-www-data}"

echo "▶ Deploying in: $APP_DIR"

# Put the app in maintenance mode (ignore if not yet bootable).
php artisan down --render="errors::503" --retry=15 || true

echo "▶ Pulling latest code…"
git fetch --all
git reset --hard origin/main

echo "▶ Installing PHP dependencies…"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "▶ Installing & building front-end…"
npm ci
npm run build

echo "▶ Linking storage (idempotent)…"
php artisan storage:link || true

echo "▶ Running migrations…"
php artisan migrate --force

echo "▶ Caching config / routes / views…"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "▶ Fixing ownership & permissions…"
chown -R "$WEB_USER:$WEB_USER" "$APP_DIR" || true
chmod -R 775 storage bootstrap/cache || true

echo "▶ Restarting queue workers…"
php artisan queue:restart || true
systemctl restart crm-worker 2>/dev/null || true

php artisan up
echo "✅ Deploy complete."
