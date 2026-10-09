#!/bin/sh
set -eu

cd /var/www/html
if [ ! -f artisan ] || [ ! -f composer.lock ] || [ ! -f package-lock.json ]; then
    echo 'The GoMove Laravel project must be mounted at /var/www/html.' >&2
    exit 1
fi
if [ -z "${APP_KEY:-}" ]; then
    echo 'APP_KEY is missing. Run scripts/init-compose-env.py and load .env.compose.' >&2
    exit 1
fi

# With rootless keep-id, www-data writes files as the user who launched Podman.
as_app() { runuser -u www-data -- "$@"; }
as_app mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache
if [ ! -f .env ]; then
    as_app cp .env.example .env
fi

php /usr/local/bin/gomove-wait-for-database.php

if [ "${WEB_BOOTSTRAP:-true}" = true ]; then
    as_app composer install --no-interaction --prefer-dist
    as_app php artisan config:clear
    as_app php artisan view:clear
    # A mounted Vite development marker must not override the built asset manifest.
    as_app rm -f public/hot
    as_app npm ci --no-audit --no-fund
    as_app npm run build
    as_app php artisan migrate --force
fi

exec docker-php-entrypoint "$@"
