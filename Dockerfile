# Use PHP 8.2 with Apache as the base image
FROM php:8.2-apache

# Set working directory to Apache document root
WORKDIR /var/www/html

# Install system dependencies required for PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for .htaccess support
RUN a2enmod rewrite

# Copy the entire application to the container
COPY . /var/www/html/

# Set proper file permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/exports

# Create logs directory for PHP error logging
RUN mkdir -p /var/www/html/logs \
    && chown www-data:www-data /var/www/html/logs

# Expose port 80 for the web server
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
