# Usamos una imagen base de PHP 8.2
FROM php:8.2-cli

# Instalamos utilidades del sistema necesarias para Laravel y Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Instalamos Composer dentro del contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definimos la carpeta de trabajo
WORKDIR /app

# Copiamos todos tus archivos al contenedor
COPY . .

# Instalamos las dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Exponemos el puerto 10000 (el que usa Render)
EXPOSE 10000

# Comando para iniciar el servidor
CMD php artisan serve --host 0.0.0.0 --port 10000