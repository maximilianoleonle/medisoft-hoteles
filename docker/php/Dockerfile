FROM php:8.2-apache

# DocumentRoot del sistema real
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html

# Instalamos herramientas y extensiones comunes para sistemas PHP + MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql mysqli zip \
    && a2enmod rewrite headers expires deflate \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Evitar warning de ServerName en Apache
RUN printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Permitir .htaccess en public_html
RUN printf '<Directory /var/www/html/public_html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/medisoft-public-html.conf \
    && a2enconf medisoft-public-html

# Cargamos configuración personalizada de PHP
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Carpeta raíz del proyecto dentro del contenedor
WORKDIR /var/www/html
