#!/usr/bin/env sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY belum diset. Generate dengan: echo \"base64:\$(openssl rand -base64 32)\"" >&2
    exit 1
fi

# Cache lama bisa berasal dari image sebelumnya (APP_KEY / DB berbeda).
php artisan config:clear >/dev/null 2>&1 || true
php artisan package:discover --ansi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan view:cache || true
php artisan storage:link >/dev/null 2>&1 || true

# Render/Vercel-style platforms memberi port lewat $PORT. Apache default 80.
PORT="${PORT:-80}"
if [ "$PORT" != "80" ]; then
    sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

exec apache2-foreground
