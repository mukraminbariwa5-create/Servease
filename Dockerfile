# Servease public demo: PHP 8.3 + Apache with a small MariaDB inside the same container.
# Built and run by Render (see render.yaml and docker/README.md). The database is created and
# filled with fresh demo data every time the container starts.
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server libpng-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers expires deflate \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY . ./
COPY docker/config.php ./config/config.php
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/mpm.conf /etc/apache2/conf-enabled/zz-servease-mpm.conf
COPY docker/mariadb.cnf /etc/mysql/mariadb.conf.d/90-servease.cnf
COPY docker/start.sh /usr/local/bin/servease-start

# The brand-tinted photos are already made; keep them newer than their sources so they're reused.
RUN chmod +x /usr/local/bin/servease-start \
    && touch assets/img/photos/duo/*.jpg \
    && rm -rf docker .github tests docs deploy _old \
    && chown -R www-data:www-data storage uploads

ENV PORT=10000
EXPOSE 10000
CMD ["servease-start"]
