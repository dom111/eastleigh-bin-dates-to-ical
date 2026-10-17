FROM php:7-fpm-alpine

ARG APP_UID

# No sudo for the app user: if PHP is compromised, it must not be able to become root.
RUN apk add --no-cache libzip-dev postgresql-dev gnu-libiconv \
    && adduser -D -u $APP_UID -h /home/app app \
    && docker-php-ext-install zip pdo_pgsql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    ;

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

WORKDIR /var/www

USER app
