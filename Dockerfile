# STAGE 1: Frontend Build (Node.js)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install && npm cache clean --force
COPY . .
RUN npm run build

# STAGE 2: PHP Dependencies (Composer)
FROM composer:latest AS dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

# STAGE 3: Backend Production (PHP 8.3 Apache)
FROM php:8.3-apache-bullseye

# Set production environment variables early
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_OPCACHE_ENABLE=1

# Install system dependencies (consolidated, minimal set)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    ghostscript \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_pgsql mbstring exif pcntl bcmath gd zip imagick redis

# Tingkatkan limit upload PHP menjadi 50MB
RUN echo "upload_max_filesize = 50M\npost_max_size = 50M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/custom-uploads.ini    

# Fix ImageMagick PDF policy
RUN sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml || true

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache VirtualHost
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy application source
COPY . .

# Copy Composer binaries and dependencies from builder
COPY --from=dependencies /usr/bin/composer /usr/bin/composer
COPY --from=dependencies /app/vendor ./vendor

# Copy frontend build artifacts
COPY --from=frontend /app/public/build ./public/build

# Set permissions
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/cache \
               /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views \
               /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create entrypoint script
RUN echo "#!/bin/bash" > /usr/local/bin/start-container && \
    echo "set -e" >> /usr/local/bin/start-container && \
    echo "php artisan package:discover --ansi" >> /usr/local/bin/start-container && \
    echo "php artisan config:cache --ansi" >> /usr/local/bin/start-container && \
    echo "php artisan route:cache --ansi" >> /usr/local/bin/start-container && \
    echo "php artisan view:cache --ansi" >> /usr/local/bin/start-container && \
    echo "php artisan migrate --force --ansi" >> /usr/local/bin/start-container && \
    echo "apache2-foreground" >> /usr/local/bin/start-container && \
    chmod +x /usr/local/bin/start-container

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

CMD ["start-container"]
