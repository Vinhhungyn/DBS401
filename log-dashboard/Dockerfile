FROM php:8.1-apache
RUN apt-get update && apt-get install -y procps && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN usermod -u 999 www-data
COPY index.php /var/www/html/index.php