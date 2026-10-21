FROM php:8.5.11-zts-alpine3.24@sha256:1c45229fd82d57ae54104a0930fb58c635bbfe81dc156971eeaf7993bc59db32 AS runtime

# Official 8.5.12 image indexes have no platform manifests yet. Build the
# security release from php.net, preserving the official ZTS configuration.
ENV PHP_VERSION=8.5.12 \
    PHP_URL=https://www.php.net/distributions/php-8.5.12.tar.xz \
    PHP_ASC_URL=https://www.php.net/distributions/php-8.5.12.tar.xz.asc \
    PHP_SHA256=08bc2eba2a588654ade1357174aa646b761556c862dda0678345ee29ed481209

RUN apk add --no-cache --upgrade 'ca-certificates>=20261020-r0' ca-certificates-bundle \
    && update-ca-certificates
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt \
    CURL_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt \
    COMPOSER_CAFILE=/etc/ssl/certs/ca-certificates.crt
RUN printf '%s\n' \
      'openssl.cafile=/etc/ssl/certs/ca-certificates.crt' \
      'curl.cainfo=/etc/ssl/certs/ca-certificates.crt' \
      > "$PHP_INI_DIR/conf.d/ca.ini"

COPY docker/rebuild-php.sh /usr/local/bin/rebuild-php
RUN sh /usr/local/bin/rebuild-php && rm /usr/local/bin/rebuild-php

RUN apk add --no-cache \
      --repository https://dl-cdn.alpinelinux.org/alpine/edge/testing \
      cowsay \
      fortune

FROM runtime AS dependencies
COPY --from=composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock /app/
RUN composer validate --strict \
    && composer install --no-dev --no-interaction --prefer-dist -o \
    && composer check-platform-reqs --no-dev

FROM runtime
WORKDIR /app
COPY composer.json composer.lock phap.php start.sh /app/
COPY assets /app/assets
COPY --from=dependencies /app/vendor /app/vendor
RUN touch .env
ENTRYPOINT ["/app/start.sh"]
