#!/usr/bin/env bash
# Production deploy. Run ON the server from the project root:
#   ./deploy.sh            # pull + composer (if lockfile moved) + migrate + caches
#   ./deploy.sh --seed     # ...and re-run the seeders (idempotent: only inserts missing games)
set -euo pipefail
cd "$(dirname "$0")"

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
git pull
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ]; then
    echo "→ composer install (lockfile changed)"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

echo "→ migrate"
php artisan migrate --force

if [ "${1:-}" = "--seed" ]; then
    echo "→ db:seed"
    php artisan db:seed --force
fi

echo "→ rebuild caches"
php artisan optimize:clear
php artisan optimize
php artisan storage:link >/dev/null 2>&1 || true
echo "✓ deployed"
