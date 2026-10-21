#!/usr/bin/env bash
# Production deploy for the NikniQ Games portal. Run ON the server from the
# project root (cx.sh checks the setup and can fix it; deploy.sh updates it):
#
#   ./deploy.sh                 # pull + composer (if lockfile moved) + migrate + caches
#   ./deploy.sh GamesSeeder     # ...and re-run one seeder class
#   ./deploy.sh --seed          # ...and re-run every seeder (confirms first)
#
# Seeders are idempotent (firstOrCreate by slug), so re-seeding only inserts
# missing games and never overwrites admin edits or client credentials.
set -euo pipefail
cd "$(dirname "$0")"

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
git pull
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ]; then
    if command -v composer >/dev/null 2>&1; then
        echo "→ composer install (lockfile changed)"
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo "⚠ composer.lock changed but composer is not on PATH — install dependencies manually."
    fi
fi

echo "→ migrate"
php artisan migrate --force

if [ "${1:-}" = "--seed" ]; then
    read -r -p "Re-run every seeder? Type 'yes' to continue: " confirm
    if [ "$confirm" = "yes" ]; then
        echo "→ db:seed (all)"
        php artisan db:seed --force
    else
        echo "→ skipped seeding"
    fi
elif [ "${1:-}" != "" ]; then
    echo "→ db:seed --class=$1"
    php artisan db:seed --class="$1" --force
fi

echo "→ rebuild caches"
php artisan optimize:clear
php artisan optimize
[ -e public_html/storage ] || php artisan storage:link >/dev/null 2>&1 || true

echo "✓ deployed $(git log --oneline -1)"
