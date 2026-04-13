FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev \
    libxml2-dev libpq-dev libzip-dev \
    && docker-php-ext-install \
        pdo pdo_pgsql pgsql \
        mbstring exif pcntl bcmath \
        gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN usermod -u 1000 www-data

RUN mkdir -p /var/www/.composer/cache \
    && chown -R www-data:www-data /var/www/.composer

RUN mkdir -p /var/www \
    && printf '[safe]\n\tdirectory = /var/www/html\n' > /var/www/.gitconfig \
    && chown www-data:www-data /var/www/.gitconfig

WORKDIR /var/www/html

USER www-data
