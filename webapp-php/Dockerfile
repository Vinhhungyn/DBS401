FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

RUN sed -i 's|CustomLog ${APACHE_LOG_DIR}/access.log combined|CustomLog /var/log/apache2/access_vuln.log combined|' /etc/apache2/sites-enabled/000-default.conf \
    && sed -i 's|ErrorLog ${APACHE_LOG_DIR}/error.log|ErrorLog /var/log/apache2/error_vuln.log|' /etc/apache2/sites-enabled/000-default.conf

# Dùng CMD để chạy sau khi volume đã mount (khác với RUN là lúc build)
CMD bash -c " \
    touch /var/log/apache2/access_vuln.log /var/log/apache2/error_vuln.log && \
    chown www-data:adm /var/log/apache2/access_vuln.log /var/log/apache2/error_vuln.log && \
    chmod 664 /var/log/apache2/access_vuln.log /var/log/apache2/error_vuln.log && \
    exec apache2-foreground"

EXPOSE 80