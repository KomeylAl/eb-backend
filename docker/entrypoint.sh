#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Named volumes are often root-owned on first mount.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache || true
    chmod -R ug+rwx storage bootstrap/cache || true
fi

# Only warm caches when starting the long-lived web stack.
if [ "${SKIP_OPTIMIZE:-false}" != "true" ] && [ -f artisan ] && [ "$#" -gt 0 ]; then
    case "$1" in
        /usr/bin/supervisord|supervisord)
            php artisan storage:link --force >/dev/null 2>&1 || true
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            ;;
    esac
fi

exec "$@"
