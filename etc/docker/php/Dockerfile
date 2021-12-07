FROM docker.io/php:8.0.13-fpm-alpine3.15

WORKDIR /code

RUN chown www-data:www-data /code

RUN docker-php-ext-install opcache

# Allows for us to wait on MongoDB to start, before running commands in the PHP container.
# See `./bin/container-console`.
RUN set -xe \
	&& curl -fsSL -o /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/v2.4.1/waitforit-linux_amd64 \
	&& chmod +x /usr/local/bin/waitforit


######
### MongoDB
######

RUN \
    apk add --no-cache --virtual .mongodb-ext-build-deps openssl-dev autoconf gcc g++ make && \
    pecl install mongodb && \
    echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini && \
    pecl clear-cache && \
    apk del .mongodb-ext-build-deps

######
### /MongoDB
######

COPY override.ini /usr/local/etc/php/conf.d/override.ini

# We intentionally prefix the file with `zz-`, to make it load after other files.
# Files are loaded in alphabetical order. Since we'd like to override `www.conf`,
# we'd need to be sorted after it.
COPY override.conf /usr/local/etc/php-fpm.d/zz-override.conf

# We install Nagios in the PHP image merely for configuration-validation purposes.
# The actual Nagios service that we use runs in a separate container.
RUN apk add --no-cache nagios
