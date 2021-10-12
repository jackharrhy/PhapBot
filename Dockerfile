FROM php:8.0-fpm-alpine3.14

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    docker-php-ext-install bcmath; \
    sed -i '/phpize/i \
    [[ ! -f "config.m4" && -f "config0.m4" ]] && mv config0.m4 config.m4' \
    /usr/local/bin/docker-php-ext-configure; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    mkdir /app && \
    rm -rf /var/cache/apk/*

WORKDIR /app

COPY composer.json composer.lock phap.php /app/
COPY start.sh /

RUN cd /app && composer install --no-dev -o

RUN touch .env

ENTRYPOINT ["/start.sh"]
