FROM php:7.3-apache

RUN apt-get update

RUN apt-get install --yes apt-utils

RUN apt-get install --yes --no-install-recommends libpq-dev curl wget git unzip libonig-dev gnupg2 ca-certificates \
  && docker-php-ext-install pgsql \
  && docker-php-ext-install pdo_pgsql

RUN apt-get install --yes --no-install-recommends zlib1g-dev libzip-dev
RUN docker-php-ext-install zip

RUN docker-php-ext-install exif

RUN apt-get update --fix-missing
RUN apt-get install --yes --no-install-recommends libfreetype6-dev zlib1g-dev libpng-dev libjpeg-dev libwebp-dev libxpm-dev

RUN docker-php-ext-install mbstring

RUN docker-php-ext-install gd

RUN a2enmod rewrite

RUN a2enmod headers

  # Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    ln -s /root/.composer/vendor/bin/drush /usr/local/bin/drush

# Install Drush
RUN composer global require drush/drush:8.1.* && \
    composer global update

RUN apt-get install -y libmemcached-dev libzip-dev zlib1g

RUN mkdir -p /usr/src/php/ext/memcached
WORKDIR /usr/src/php/ext/memcached
RUN wget https://github.com/php-memcached-dev/php-memcached/archive/v3.1.3.zip; unzip /usr/src/php/ext/memcached/v3.1.3.zip
RUN mv /usr/src/php/ext/memcached/php-memcached-3.1.3/* /usr/src/php/ext/memcached/

RUN docker-php-ext-configure memcached && docker-php-ext-install memcached 

RUN a2enmod auth_digest
RUN a2enmod proxy_http
RUN a2enmod proxy
RUN a2enmod socache_shmcb
RUN a2enmod ssl

# Clean repository
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /root