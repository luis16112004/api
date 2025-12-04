FROM php:8.2-cli

# EL SECRETO DE VELOCIDAD: Script para instalar extensiones ya listas
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instalar librerías del sistema y extensiones (zip, grpc, bcmath, intl) en UN solo paso
RUN install-php-extensions zip grpc bcmath intl

# Instalar git y unzip
RUN apt-get update && apt-get install -y git unzip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

EXPOSE 10000
CMD php artisan serve --host 0.0.0.0 --port 10000