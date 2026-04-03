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

# Copy composer files first (for Docker layer caching)
COPY composer.json composer.lock ./

# Validate composer files and install deps
RUN composer validate \
    && composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy full project files
COPY . .

# Re-validate and ensure autoloader (handles any changes)
RUN composer validate --no-check-all \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative --apcu \
    && test -f vendor/composer/autoload_real.php || (echo 'Autoloader missing - failing build' && exit 1)

# Install Node dependencies (optional build)
RUN if [ -f package.json ]; then npm ci --only=production && npm run build || true; else echo 'No package.json'; fi

# Run post-install scripts safely
RUN composer run-script post-install-cmd || true

# Create writable directories with correct permissions
RUN mkdir -p uploads exports public/qr logs database/seeds \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 uploads exports public/qr logs

# Apache configuration
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && a2enmod headers \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!/var/www/html/public!/var/www/html/public!g' /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
