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

# Enable Apache modules for .htaccess support
RUN a2enmod rewrite headers expires deflate

# Copy the entire application to the container
COPY . /var/www/html/

# Create custom Apache configuration for public directory as document root
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # Protect sensitive directories\n\
    <Directory /var/www/html/classes>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/config>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/helpers>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/middleware>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/tests>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/views>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/vendor>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/database>\n\
        Require all denied\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

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
