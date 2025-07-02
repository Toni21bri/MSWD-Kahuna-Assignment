FROM php:8.2-fpm

ARG WITH_XDEBUG=true

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        zip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-install zip; \
    if [ $WITH_XDEBUG = "true" ] ; then \
        pecl install xdebug; \
    fi ;

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chmod=700 scripts/run.sh /opt/run.sh
CMD /opt/run.sh