FROM php:8.3-apache

# Instalar extensiones PDO MySQL necesarias para los adaptadores de persistencia
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers

# Configurar DocumentRoot hacia public/ con FallbackResource hacia el Front Controller
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1\n<Directory /var/www/html/public>\n    Options -Indexes +FollowSymLinks\n    AllowOverride All\n    CGIPassAuth On\n    FallbackResource /index.php\n    Require all granted\n</Directory>\n" > /etc/apache2/conf-available/hexagonal.conf \
    && a2enconf hexagonal

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/storage/cache \
             /var/www/html/storage/queue \
             /var/www/html/storage/logs/emails \
             /var/www/html/storage/data \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod +x /var/www/html/docker/apache-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/apache-entrypoint.sh"]