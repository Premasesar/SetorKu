# Dockerfile untuk SetorKu (Laravel 11 + PHP 8.3 Apache)
# Opsi deployment Docker mandiri tanpa mengubah alur kerja lokal.

FROM php:8.3-apache

# 1. Pasang paket sistem yang dibutuhkan
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Aktifkan modul rewrite dan headers Apache
RUN a2enmod rewrite headers

# 3. Pasang konfigurasi VirtualHost Apache untuk mengarah ke /public
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# 4. Ambil binary Composer resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5. Atur direktori kerja
WORKDIR /var/www/html

# 6. Salin kode aplikasi
COPY . /var/www/html

# 7. Pasang dependensi PHP untuk production (tanpa paket dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 8. Atur izin direktori storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Buka port 80
EXPOSE 80

# 10. Jalankan Apache di foreground
CMD ["apache2-foreground"]
