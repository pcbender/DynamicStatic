FROM php:8.2-fpm-alpine
RUN apk add --no-cache git zip unzip oniguruma-dev icu-dev libzip-dev sqlite-dev autoconf g++ make bash curl
RUN docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) intl pdo pdo_mysql pdo_sqlite opcache
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /var/www/html
