FROM php:7.2-fpm-alpine
RUN mkdir -p /usr/src/phpapp
WORKDIR /usr/src/phpapp
COPY ./composer.json ./composer.lock* ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --update \
    composer \
    icu-dev \
    && rm -rf /var/cache/apk/*
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && docker-php-ext-install intl \
    && docker-php-ext-install pdo \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install calendar \
    && docker-php-ext-install ctype \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install exif \
    && docker-php-ext-install sockets \
    && docker-php-ext-install json \
    && docker-php-ext-install fileinfo \
    && docker-php-ext-install shmop 
RUN chmod 755 /usr/local/bin/docker-php-entrypoint
COPY . ./
EXPOSE 9000
CMD ["sh","-c","php -S localhost:9000 -t public && php artisan queue:work --sleep=3 --tries=3"] 






