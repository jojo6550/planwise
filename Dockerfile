FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy project files
COPY . .

# Install Node dependencies and build assets (if any)
RUN npm install && npm run build || true

# Run composer post scripts
RUN composer run-script post-install-cmd || true

# Create and set permissions for writable directories
RUN mkdir -p uploads exports public/qr logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 uploads exports public/qr logs

# Apache config
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && a2enmod headers

EXPOSE 80

CMD ["apache2-foreground"]

