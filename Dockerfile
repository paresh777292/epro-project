FROM php:8.2-apache

# MySQLi extension enable karein
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Project files ko server mein copy karein
COPY . /var/www/html/

# Apache rewrite enable karein
RUN a2enmod rewrite

EXPOSE 80