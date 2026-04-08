FROM php:8.1-apache

# Install dependencies and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli

# Enable Apache mod_rewrite for nice URLs if needed
RUN a2enmod rewrite

# Copy everything from current directory into the container's web root
COPY . /var/www/html/

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port (Railway usually assigns PORT dynamically, but exposing 80 handles the internal routing)
EXPOSE 80

# Overwrite Apache default port to listen to Railway's $PORT variable dynamically
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
