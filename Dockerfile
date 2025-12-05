FROM php:8.2-cli

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions zip grpc bcmath intl pdo_mysql

RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

RUN chmod -R 775 storage bootstrap/cache

RUN rm -rf /root/.composer/cache

ENV PORT=8000

EXPOSE $PORT

CMD php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=$PORT