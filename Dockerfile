FROM php:8.2-apache

# Enabling mod_rewrite for Apache (for RewriteEngine)
RUN a2enmod rewrite

# Installing MySQL drivers
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Restarting Apache to apply changes
RUN service apache2 restart