FROM php:5.6-cli

MAINTAINER Rob Wittman <rob@ihsdigital.com>

RUN apt-get update && apt-get install -y -q --no-install-recommends \
    curl \
    git \
    php5-cli

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . /opt
WORKDIR /opt

EXPOSE 8001

RUN composer install

RUN rm -r /opt/example/vendor/robwittman/leaky-bucket-rate-limiter/src  && ln -s /opt/src /opt/example/vendor/robwittman/leaky-bucket-rate-limiter/src
CMD ["php", "-S", "0.0.0.0:8001", "-t", "/opt/example"]
