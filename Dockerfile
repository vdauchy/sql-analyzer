FROM php:8.0-cli-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions \
    ast \
    pcov \
    pdo_mysql

RUN apk add --no-cache \
    bash

WORKDIR /usr/src/app