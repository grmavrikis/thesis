FROM php:8.2-apache

# Ενεργοποίηση του mod_rewrite (για το RewriteEngine)
RUN a2enmod rewrite

# Εγκατάσταση των drivers για MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Επανεκκίνηση του Apache για να δει τις αλλαγές
RUN service apache2 restart