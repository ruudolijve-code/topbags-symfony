FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libicu-dev \
    autoconf \
    g++ \
    make \
    pkg-config \
    $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql intl zip opcache

RUN pecl channel-update pecl.php.net
RUN yes '' | pecl install redis
RUN docker-php-ext-enable redis

COPY conf.d/*.ini /usr/local/etc/php/conf.d/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer