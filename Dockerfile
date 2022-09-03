FROM php:8.2-rc-zts-alpine3.16

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock phap.php start.sh /app/

RUN cd /app && composer install --no-dev -o

RUN touch .env

ENTRYPOINT ["/app/start.sh"]
