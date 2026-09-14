FROM php:8.2-fpm-alpine

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Optional: Install other extensions if needed (e.g. opcache)
# RUN docker-php-ext-install opcache
