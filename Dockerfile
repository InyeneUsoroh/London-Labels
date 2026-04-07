# 1. Base Image - A professional PHP server
FROM php:8.3-apache

# 2. Install the Bridge - Force-install MySQL & Image Processing
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath

# 3. Copy Your Boutique Code
COPY . /var/www/html/

# 4. Permissions - Enable users to upload their own photos
RUN mkdir -p /var/www/html/Uploads && chmod -R 777 /var/www/html/Uploads

# 5. Enable Apache Mod Rewrite (For clean URLs)
RUN a2enmod rewrite

# 6. Open the Port
EXPOSE 80
