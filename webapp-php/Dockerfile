# ============================================================
# Dockerfile — PHP webapp (thay thế Python/Flask)
# Image: php:8.2-apache với extension mysqli
# ============================================================
FROM php:8.2-apache

# Cài mysqli extension (tương đương mysql-connector-python)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy toàn bộ PHP source vào document root của Apache
COPY . /var/www/html/

# Cấp quyền cho Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
