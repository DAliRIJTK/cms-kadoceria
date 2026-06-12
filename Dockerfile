# STAGE 1: Frontend Build (Node.js)
FROM node:20 AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# STAGE 2: Backend Setup (PHP 8.3 Apache)
FROM php:8.3-apache

# 1. Instal dependensi sistem Linux (TAMBAHAN: git dan libzip-dev)
RUN apt-get update && apt-get install -y \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    ghostscript \
    && rm -rf /var/lib/apt/lists/*

# 2. Instal ekstensi PHP native (TAMBAHAN: zip)
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# 3. Instal ekstensi Imagick menggunakan script mlocati (Lebih stabil untuk PHP 8.3)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imagick

# 4. FIX KRITIKAL: Ubah policy ImageMagick agar diizinkan membaca & menulis PDF
RUN sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml || true

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite
WORKDIR /var/www/html

# Salin seluruh source code
COPY . .

# Salin hasil build Vite dari Stage 1
COPY --from=frontend /app/public/build ./public/build

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Skrip entrypoint container
RUN echo "#!/bin/bash" > /usr/local/bin/start-container && \
    echo "php artisan package:discover --ansi" >> /usr/local/bin/start-container && \
    echo "php artisan config:clear" >> /usr/local/bin/start-container && \
    echo "php artisan cache:clear" >> /usr/local/bin/start-container && \
    echo "php artisan migrate --force" >> /usr/local/bin/start-container && \
    echo "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache" >> /usr/local/bin/start-container && \
    echo "apache2-foreground" >> /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container
CMD ["start-container"]