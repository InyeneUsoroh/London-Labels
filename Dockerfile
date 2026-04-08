# 1. Base Image - A professional PHP server
FROM php:8.3-apache

# 2. Install the Bridge - Force-install MySQL & Image Processing
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath

# 3. Install Composer (The "Library Baker")
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Copy Your Boutique Code
COPY . /var/www/html/

# 5. Run the "Baking" - Install dependencies inside the container
RUN composer install --no-interaction --optimize-autoloader

# 6. Permissions - Enable users to upload their own photos
RUN mkdir -p /var/www/html/Uploads && chmod -R 777 /var/www/html/Uploads

# 7. Enable Apache Mod Rewrite and Fix MPMs
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 8. Open the Port
EXPOSE 80
