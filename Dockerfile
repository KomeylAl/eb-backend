# syntax=docker/dockerfile:1

############################
# Base: FrankenPHP + PHP extensions (serves HTTP directly, no nginx/php-fpm)
############################
FROM dunglas/frankenphp:1-php8.4-alpine AS base

RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        gd \
        zip \
        exif \
        pcntl \
        opcache \
        redis

WORKDIR /app

############################
# Composer dependencies + optimized autoloader
############################
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi

############################
# Production runtime
############################
FROM base AS runner

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --from=vendor /app /app

COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
