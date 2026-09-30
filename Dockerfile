FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        git \
        unzip \
        rabbitmq-c-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache \
    && pecl install amqp \
    && docker-php-ext-enable amqp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN if [ -f composer.json ]; then composer install --no-interaction --optimize-autoloader --no-scripts; fi

EXPOSE 9000
CMD ["php-fpm"]
