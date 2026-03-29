FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite headers expires deflate

# Install system libraries and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip mbstring opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP runtime configuration
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=12M'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_time=300'; \
    echo 'memory_limit=256M'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'session.cookie_httponly=On'; \
    echo 'session.use_strict_mode=On'; \
} > /usr/local/etc/php/conf.d/planwise.ini

# Install Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create writable runtime directories
RUN mkdir -p public/qr exports uploads logs \
    && chown -R www-data:www-data public/qr exports uploads logs \
    && chmod -R 775 public/qr exports uploads logs

# Point Apache document root at public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

EXPOSE 80
