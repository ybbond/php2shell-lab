ARG PHP_TAG=8.4.26-apache
FROM php:${PHP_TAG}

# Label informasi untuk keperluan demo / kuliah
ARG PHP_STATUS="unknown"
ENV LAB_PHP_STATUS=${PHP_STATUS}

# Buat direktori sesi khusus agar mudah diamati saat demo
RUN mkdir -p /tmp/php_sessions && chown -R www-data:www-data /tmp/php_sessions

WORKDIR /var/www/html
EXPOSE 80
