FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libapache2-mod-security2 \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli \
    && a2enmod security2 \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

# Copy tung file rule rieng le
RUN mkdir -p /tmp/modsecurity /etc/modsecurity/rules

COPY modsecurity/modsecurity.conf /etc/modsecurity/modsecurity.conf
COPY modsecurity/rule-sql.conf /etc/modsecurity/rules/rule-sql.conf
COPY modsecurity/rule-fileupload.conf /etc/modsecurity/rules/rule-fileupload.conf
COPY modsecurity/rule-misconfig.conf /etc/modsecurity/rules/rule-misconfig.conf
COPY modsecurity/rule-cookie.conf /etc/modsecurity/rules/rule-cookie.conf
COPY modsecurity/rule-xss.conf /etc/modsecurity/rules/rule-xss.conf

RUN chown -R www-data:www-data /var/www/html

    RUN sed -i 's|CustomLog \${APACHE_LOG_DIR}/access.log combined|CustomLog /var/log/apache2/access_real.log combined|' /etc/apache2/sites-enabled/000-default.conf \
    && sed -i 's|ErrorLog \${APACHE_LOG_DIR}/error.log|ErrorLog /var/log/apache2/error_real.log|' /etc/apache2/sites-enabled/000-default.conf \
    && touch /var/log/apache2/access_real.log /var/log/apache2/error_real.log \
    && chown www-data:adm /var/log/apache2/access_real.log /var/log/apache2/error_real.log \
    && chmod 664 /var/log/apache2/access_real.log /var/log/apache2/error_real.log

EXPOSE 80