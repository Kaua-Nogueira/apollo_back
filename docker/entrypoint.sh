#!/bin/sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "ERRO: APP_KEY não foi configurada. Gere uma chave com: php artisan key:generate --show" >&2
    exit 1
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    database_path="${DB_DATABASE:-/var/www/html/database/data/database.sqlite}"
    mkdir -p "$(dirname "$database_path")"
    touch "$database_path"
    chown www-data:www-data "$database_path"
fi

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force
php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
