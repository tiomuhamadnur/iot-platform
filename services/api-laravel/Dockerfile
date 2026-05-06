FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

FROM php:8.2-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app/vendor /app/vendor
COPY . .

RUN mkdir -p /app/bootstrap/cache /app/storage/logs \
    && chmod -R 777 /app/bootstrap/cache /app/storage \
    && php artisan package:discover --ansi

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
