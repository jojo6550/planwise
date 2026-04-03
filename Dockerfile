FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy full project first
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Optional: Install Node deps if present
RUN if [ -f package.json ]; then npm ci --only=production && npm run build; fi

# Ensure writable directories
RUN mkdir -p uploads exports public/qr logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 uploads exports public/qr logs

# Apache config
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
    && a2enmod rewrite headers dir \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
CMD ["apache2-foreground"]