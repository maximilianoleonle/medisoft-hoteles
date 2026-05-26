FROM php:8.2-apache

# Instalamos herramientas y extensiones comunes para sistemas PHP + MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Cargamos configuración personalizada de PHP
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Carpeta donde Apache sirve los archivos PHP
WORKDIR /var/www/html
