#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ ! -f ".env" ]]; then
    if [[ -f ".env.deepnote.example" ]]; then
        cp ".env.deepnote.example" ".env"
        echo "Copied .env.deepnote.example to .env. Fill in the real secrets before re-running." >&2
        exit 1
    fi

    echo "Missing .env file." >&2
    exit 1
fi

composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deepnote bootstrap complete."
