ARG PHP_TAG=8.4.26-apache
FROM php:${PHP_TAG} AS base

# Buat direktori sesi khusus agar mudah diamati saat demo
RUN mkdir -p /tmp/php_sessions && chown -R www-data:www-data /tmp/php_sessions
# Toolchain build tidak dipakai saat runtime
RUN apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /usr/src/* /usr/local/include/* /var/lib/apt/lists/* /var/cache/apt/* /usr/share/doc/*

# Satu layer: hapus di atas tidak mengecilkan image selama layer lama masih ada.
# COPY tidak membawa config image dasar, jadi ENV/ENTRYPOINT/CMD ditulis ulang.
FROM scratch
COPY --from=base / /
ENV PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin \
    PHP_INI_DIR=/usr/local/etc/php \
    APACHE_CONFDIR=/etc/apache2 \
    APACHE_ENVVARS=/etc/apache2/envvars

# Label informasi untuk keperluan demo / kuliah
ARG PHP_STATUS="unknown"
ENV LAB_PHP_STATUS=${PHP_STATUS}

WORKDIR /var/www/html
EXPOSE 80
STOPSIGNAL SIGWINCH
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["apache2-foreground"]
