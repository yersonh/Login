FROM php:8.2-fpm

# Instalar Apache SEPARADAMENTE para tener control total
RUN apt-get update && apt-get install -y \
    apache2 \
    libapache2-mod-php8.2

# Configurar Apache MANUALMENTE
RUN a2dismod mpm_event mpm_worker && \
    a2enmod mpm_prefork rewrite headers php8.2 && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Instalar extensiones PHP
RUN apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip

RUN pecl install redis && docker-php-ext-enable redis

# Puerto
ENV PORT=8080
RUN sed -i "s/Listen 80/Listen 8080/g" /etc/apache2/ports.conf && \
    sed -i "s/:80/:8080/g" /etc/apache2/sites-available/*.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

CMD ["apache2ctl", "-D", "FOREGROUND"]