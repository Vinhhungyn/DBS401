# ============================================================
# Dockerfile — PHP webapp vulnerable (port 5000)
# ============================================================
FROM php:8.2-apache

# Cài mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy toàn bộ PHP source
COPY . /var/www/html/

# Cấp quyền cho Apache
RUN chown -R www-data:www-data /var/www/html

# Ghi Apache log ra file thật để dashboard đọc được
RUN sed -i 's|CustomLog ${APACHE_LOG_DIR}/access.log combined|CustomLog /var/log/apache2/access_vuln.log combined|' /etc/apache2/sites-enabled/000-default.conf \
    && sed -i 's|ErrorLog ${APACHE_LOG_DIR}/error.log|ErrorLog /var/log/apache2/error_vuln.log|' /etc/apache2/sites-enabled/000-default.conf \
    && touch /var/log/apache2/access_vuln.log /var/log/apache2/error_vuln.log \
    && chown www-data:adm /var/log/apache2/access_vuln.log /var/log/apache2/error_vuln.log \
    && chmod 664 /var/log/apache2/access_vuln.log /var/log/apache2/error_vuln.log

EXPOSE 80