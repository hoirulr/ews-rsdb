FROM php:8.4-fpm-alpine

# Ekstensi yang dibutuhkan aplikasi: pdo_mysql (DB), bcmath/intl (Laravel),
# gd+zip (dompdf/export), opcache (performa), pcntl (queue worker & Reverb)
RUN apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev icu-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip gd opcache pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=60'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && { \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
        echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
