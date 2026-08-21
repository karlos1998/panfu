#!/bin/sh
set -eu

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage

if [ "${APP_ENV:-}" = "production" ]; then
    su-exec www-data php artisan config:cache --no-interaction
    su-exec www-data php artisan view:cache --no-interaction
fi

exec su-exec www-data "$@"
