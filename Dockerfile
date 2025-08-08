FROM php:8.2-rc-zts-alpine3.16

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock phap.php start.sh /app/

RUN cd /app && composer install --no-dev -o

RUN touch .env

RUN apk update
RUN apk add cowsay --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/
RUN apk add fortune --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/

ENTRYPOINT ["/app/start.sh"]
