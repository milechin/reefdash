FROM php:8.2-apache

# Copy all project files into the web root
COPY . /var/www/html/

# Ensure the web server can write tank_data.js (needed by save.php)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
