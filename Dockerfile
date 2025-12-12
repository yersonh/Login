FROM php:8.2-apache

# === SOLUCIÓN DEFINITIVA PARA MPM ===
# Eliminar TODAS las configuraciones MPM existentes primero
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || true && \
    rm -f /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true

# Forzar SOLO mpm_prefork (única línea de carga)
RUN echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" > /etc/apache2/mods-enabled/mpm_prefork.load

# Verificar que solo hay uno
RUN echo "=== Verificación MPM ===" && \
    echo "Archivos en mods-enabled:" && \
    ls -la /etc/apache2/mods-enabled/ | grep mpm && \
    echo "=== MPMs cargados ===" && \
    apache2ctl -M 2>&1 | grep mpm || true

# Configuración básica de Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    a2enmod rewrite headers

# Instalar extensiones
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

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Configurar puerto para Railway
ENV PORT=8080
RUN sed -i "s/Listen 80/Listen 8080/g" /etc/apache2/ports.conf && \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:8080>/g" /etc/apache2/sites-available/000-default.conf && \
    sed -i "s/^Listen 80/Listen 8080/g" /etc/apache2/ports.conf

# Copiar proyecto
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 8080

CMD ["apache2-foreground"]