FROM php:8.2-apache

# SOLUCIÓN MÁS AGRESIVA PARA MPM
RUN echo ">>> Forzando configuración MPM..." && \
    # Deshabilitar TODOS los MPMs posibles
    rm -f /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || true && \
    rm -f /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true && \
    # Habilitar SOLO prefork
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ && \
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ && \
    # Habilitar módulos esenciales
    a2enmod rewrite headers && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# El resto IGUAL...
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

RUN pecl install redis && docker-php-ext-enable redis

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

ENV PORT=8080
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf && \
    sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]