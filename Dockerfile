# Runs the Social Affairs Management System on PHP's official Apache image.
# For real XAMPP/Windows deployment (the department's actual target
# environment) see README.md instead — this file exists purely to make the
# app deployable to free container hosts (Render, Fly.io, etc.) for a live
# online demo.
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite headers

# Composer (for PhpSpreadsheet, used by Excel import/export).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

# Debian's stock apache2.conf ships with "AllowOverride None" for /var/www/,
# which would silently disable public/.htaccess (breaking every route but
# the literal front controller). Replace the default vhost with one that
# points at public/ and allows .htaccess to run.
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Render (and most container hosts) inject $PORT at container start; Apache's
# config parser supports literal ${VAR} interpolation from the process
# environment, so both the vhost above and Listen directive pick it up.
RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf
ENV PORT=80
EXPOSE 80

CMD ["apache2-foreground"]
