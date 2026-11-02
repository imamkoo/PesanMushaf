#!/usr/bin/env sh
set -eu

# Cache lama bisa berasal dari image sebelumnya (APP_KEY / DB berbeda).
php artisan config:clear >/dev/null 2>&1 || true
php artisan package:discover --ansi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan view:cache || true
php artisan storage:link >/dev/null 2>&1 || true

exec apache2-foreground
