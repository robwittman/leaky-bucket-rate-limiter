FROM php:5.6-cli

MAINTAINER Rob Wittman <rob@ihsdigital.com>

RUN apt-get update && apt-get install -y -q --no-install-recommends \
    curl \
    git \
    php5-cli

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . /opt
WORKDIR /opt

RUN composer install
