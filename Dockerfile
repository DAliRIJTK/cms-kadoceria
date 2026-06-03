# Menggunakan image dasar PHP 8.2 yang sudah dilengkapi Apache web server
FROM php:8.4-apache

# Menginstal modul-modul sistem Linux yang dibutuhkan oleh Laravel dan PostgreSQL
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Mengaktifkan fitur mod_rewrite pada Apache agar URL Laravel berjalan mulus
RUN a2enmod rewrite

# Menentukan folder kerja di dalam container
WORKDIR /var/www/html

# Menyalin seluruh file dari repositori GitHub-mu ke dalam container
COPY . .

# Mengambil program Composer (Package manager untuk PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Menjalankan instalasi dependensi Laravel
RUN composer install --no-dev --optimize-autoloader

# Memberikan hak akses agar Laravel bisa menulis ke folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Mengubah pengaturan Apache agar langsung membaca folder /public milik Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Membuat script start-container baris demi baris agar tidak terjadi error penulisan
RUN echo "#!/bin/bash" > /usr/local/bin/start-container && \
    echo "php artisan migrate --force" >> /usr/local/bin/start-container && \
    echo "apache2-foreground" >> /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container

# Menginstruksikan Docker untuk mengeksekusi script tersebut
CMD ["start-container"]
