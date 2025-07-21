FROM php:8.3-fpm-alpine

# System dependencies
RUN apk add --no-cache bash git curl zip unzip libzip-dev icu-dev libpng-dev libjpeg-turbo-dev oniguruma-dev libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl opcache gd

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del -f .build-deps

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Symfony CLI (optional but recommended)
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www
