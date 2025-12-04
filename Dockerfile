# Usamos una imagen base de PHP 8.2
FROM php:8.2-cli

# 1. Instalamos utilidades del sistema y librerías necesarias para compilar grpc
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    autoconf \
    pkg-config \
    zlib1g-dev \
    && docker-php-ext-install zip bcmath

# 2. Instalamos la extensión gRPC (necesaria para Firebase)
# OJO: Esto puede tardar unos minutos en compilarse
RUN pecl install grpc \
    && docker-php-ext-enable grpc

# 3. Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definimos la carpeta de trabajo
WORKDIR /app

# Copiamos todos tus archivos
COPY . .

# 4. Instalamos dependencias de Laravel
# Agregamos --ignore-platform-reqs como seguridad por si falta alguna otra extensión menor
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-gd

# Exponemos el puerto
EXPOSE 10000

# Comando para iniciar
CMD php artisan serve --host 0.0.0.0 --port 10000