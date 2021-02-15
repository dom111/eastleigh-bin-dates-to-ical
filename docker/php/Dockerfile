FROM php:7-fpm-alpine

ARG APP_UID

RUN apk add sudo libzip-dev postgresql-dev \
    && adduser -D -u $APP_UID -h /home/app app && echo 'app ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers \
    && docker-php-ext-install zip pdo_pgsql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    ;

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

WORKDIR /var/www

USER app
