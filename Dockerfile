FROM php:8.5.12-zts-alpine

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apk add --no-cache --upgrade 'ca-certificates>=20261020-r0' ca-certificates-bundle \
    && update-ca-certificates
ENV COMPOSER_CAFILE=/etc/ssl/certs/ca-certificates.crt
RUN printf '%s\n' \
      'openssl.cafile=/etc/ssl/certs/ca-certificates.crt' \
      'curl.cainfo=/etc/ssl/certs/ca-certificates.crt' \
      > "$PHP_INI_DIR/conf.d/ca.ini"

WORKDIR /app

COPY composer.json composer.lock phap.php start.sh /app/
COPY assets /app/assets

RUN composer install --no-dev -o

RUN touch .env

RUN apk add --no-cache \
      --repository https://dl-cdn.alpinelinux.org/alpine/edge/testing \
      cowsay \
      fortune

ENTRYPOINT ["/app/start.sh"]
