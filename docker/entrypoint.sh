#!/bin/sh
set -e

cd /app

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# The uploads volume is often root-owned on first mount.
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Warm the caches only when starting the long-lived web server,
# not for one-off commands (migrate / queue / seed).
if [ "${SKIP_OPTIMIZE:-false}" != "true" ] && [ -f artisan ]; then
    case "$1" in
        frankenphp)
            php artisan storage:link --force >/dev/null 2>&1 || true
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            ;;
    esac
fi

exec "$@"
