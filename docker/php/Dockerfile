FROM php:8.2.8-fpm-alpine3.18

COPY --from=composer:2.5.8 /usr/bin/composer /usr/local/bin/

RUN \
    apk add --no-cache bash \
    && docker-php-ext-install mysqli pdo pdo_mysql
