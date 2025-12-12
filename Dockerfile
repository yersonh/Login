FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev libpng-dev libjpeg-dev libzip-dev libssl-dev pkg-config zip unzip curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip

RUN pecl install redis && docker-php-ext-enable redis

RUN a2enmod rewrite
RUN a2enmod headers

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

ENV PORT=8080

RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf && \
    sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# FIX Railway MPM conflict
RUN a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork

CMD ["apache2-foreground"]
