FROM docker.io/php:8.4-fpm-alpine

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

# The mongodb extension is pinned to the 1.x line. The legacy `alcaeus/mongo-php-adapter`
# (which bridges the old ext-mongo API to ext-mongodb) references the `RP_PRIMARY` read
# preference constant, which ext-mongodb 2.x removed. 1.21.5 is the last 1.x release and
# supports modern PHP. (This whole legacy Mongo layer goes away with the Symfony rewrite.)
RUN \
    apk add --no-cache --virtual .mongodb-ext-build-deps openssl-dev autoconf gcc g++ make && \
    pecl install mongodb-1.21.5 && \
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
