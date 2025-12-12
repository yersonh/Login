FROM php:8.2-apache

# SOLUCIÓN PARA EL ERROR MPM - DEBE SER LO PRIMERO
RUN echo ">>> Configurando MPM para Railway..." && \
    a2dismod -f mpm_event mpm_worker 2>/dev/null || true && \
    a2enmod mpm_prefork rewrite headers && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar extensiones para PostgreSQL, Redis y dependencias
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

# Instalar Redis extension (CRÍTICO para sesiones)
RUN pecl install redis && docker-php-ext-enable redis

# Copiar el proyecto al contenedor
COPY . /var/www/html/

# Dar permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurar Apache para usar el puerto de Railway
ENV PORT=8080

# Configurar Apache ANTES de exponer el puerto
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf && \
    sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Exponer el puerto (SOLO UNA VEZ)
EXPOSE 8080

CMD ["apache2-foreground"]