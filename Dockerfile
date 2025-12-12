FROM php:8.2-apache

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip

# Instalar extensión Redis
RUN pecl install redis && docker-php-ext-enable redis

# Habilitar módulos de Apache
RUN a2enmod rewrite
RUN a2enmod headers

# Copiar proyecto
COPY . /var/www/html/

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Apache debe escuchar en el puerto que Railway asigna dinámicamente
ENV PORT=8080
RUN echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Actualizar VirtualHost
RUN sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}

CMD ["apache2-foreground"]
