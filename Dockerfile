# syntax=docker/dockerfile:1

############################
# Shared PHP extension layer
############################
FROM php:8.4-cli-alpine AS php_ext

RUN apk add --no-cache \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

############################
# Composer dependencies
############################
FROM php_ext AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php -r "file_exists('bootstrap/cache') || mkdir('bootstrap/cache', 0775, true);" \
    && php artisan package:discover --ansi

############################
# Frontend assets (Vite)
############################
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json ./
COPY package-lock.json* ./

RUN npm install

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

############################
# Migrator / one-off artisan
############################
FROM php_ext AS migrator

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

RUN mkdir -p \
        storage/framework/{cache,sessions,views} \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "migrate", "--force"]

############################
# Production runtime
############################
FROM php:8.4-fpm-alpine AS runner

RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        icu-libs \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip \
        oniguruma \
        libpq \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && rm -rf /tmp/pear /var/cache/apk/*

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN sed -i 's/\r$//' /entrypoint.sh \
    && chmod +x /entrypoint.sh \
    && mkdir -p \
        storage/framework/{cache,sessions,views} \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
        /run/nginx \
        /var/log/supervisor \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
