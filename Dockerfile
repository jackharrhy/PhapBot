FROM php:8.5-zts-alpine

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock phap.php start.sh /app/

RUN composer install --no-dev -o

RUN touch .env

RUN apk add --no-cache \
      --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing \
      cowsay \
      fortune

ENTRYPOINT ["/app/start.sh"]
