#!/bin/sh
set -eux

# The 8.5.12 official image indexes are empty at release time. Rebuild the
# published 8.5.11 ZTS image with the same configure flags and extension set.
configure_options="$(php-config --configure-options)"
php -r '$e = get_loaded_extensions(); sort($e); echo implode("\n", $e);' > /usr/src/extensions-before
apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS argon2-dev coreutils curl-dev gnu-libiconv-dev \
    libsodium-dev libxml2-dev linux-headers oniguruma-dev openssl-dev \
    readline-dev sqlite-dev
rm -f /usr/include/iconv.h
curl -fsSL "$PHP_URL" -o /usr/src/php.tar.xz
printf '%s  %s\n' "$PHP_SHA256" /usr/src/php.tar.xz | sha256sum -c -
docker-php-source extract
cd /usr/src/php
export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS"
# php-config is from the digest-pinned official base, not application input.
eval "./configure $configure_options"
make -j2
make install
cp php.ini-* "$PHP_INI_DIR/"
cd /
docker-php-source delete
apk del --no-network .build-deps
php -r '$e = get_loaded_extensions(); sort($e); echo implode("\n", $e);' > /usr/src/extensions-after
diff -u /usr/src/extensions-before /usr/src/extensions-after
rm /usr/src/extensions-before /usr/src/extensions-after
php -r 'if (PHP_VERSION !== "8.5.12" || !PHP_ZTS) { exit(1); }'
php --version
