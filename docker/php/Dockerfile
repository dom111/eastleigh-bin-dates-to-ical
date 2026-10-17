FROM composer:2 AS composer

FROM php:8.4-fpm-alpine

ARG APP_UID

# No sudo for the app user: if PHP is compromised, it must not be able to become root.
# zip/unzip are only for Composer installing dist archives.
RUN apk add --no-cache libzip-dev unzip \
    && docker-php-ext-install zip opcache \
    && adduser -D -u $APP_UID -h /home/app app \
    ;

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www

USER app
