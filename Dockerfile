FROM composer:2.8 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-req=ext-dom \
    --ignore-platform-req=ext-fileinfo \
    --ignore-platform-req=ext-gd \
    --ignore-platform-req=ext-pcntl

COPY . .
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM node:24-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=composer /app/vendor ./vendor
RUN npm run build

FROM php:8.4-cli-alpine AS app

WORKDIR /var/www/html

RUN apk add --no-cache \
        freetype \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libzip \
        mysql-client \
        oniguruma \
        su-exec \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY docker/production/app-entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod 755 /usr/local/bin/app-entrypoint

COPY . .
COPY --from=composer /app/vendor ./vendor
COPY --from=composer /app/bootstrap/cache ./bootstrap/cache
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && chown -R www-data:www-data bootstrap/cache storage

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
