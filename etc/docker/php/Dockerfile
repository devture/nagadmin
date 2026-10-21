FROM docker.io/alpine:3.23.5

ARG PHP_VERSION=85
# renovate: datasource=github-releases depName=mongodb/mongo-php-driver
ARG MONGODB_VERSION=2.5.5

WORKDIR /code

# PHP and extensions from Alpine's repositories. The MongoDB driver is built below
# because Alpine's packaged version is too old for the patched mongodb/mongodb library.
# Beyond PHP's always-on core, the application needs:
#   ctype, iconv                    — required by composer.json
#   mbstring                        — Twig and Symfony String
#   session                         — the security firewall's form-login sessions
#   curl, openssl                   — Guzzle/Vonage over HTTPS and SMTP TLS
#   dom, xml, simplexml, xmlwriter  — Symfony loads bundle service definitions from XML
#   fileinfo, intl                  — HttpFoundation MIME detection / Symfony i18n helpers
#   phar, tokenizer                 — used by Symfony console tooling
# OPcache ships with the main php85 package (no separate extension to install).
RUN apk add --no-cache \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-ctype \
    php${PHP_VERSION}-iconv \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-session \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-openssl \
    php${PHP_VERSION}-sodium \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-simplexml \
    php${PHP_VERSION}-xmlwriter \
    php${PHP_VERSION}-fileinfo \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-phar \
    php${PHP_VERSION}-tokenizer

RUN apk add --no-cache libstdc++ \
    && apk add --no-cache --virtual .mongodb-build-deps \
        autoconf build-base linux-headers php${PHP_VERSION}-dev curl openssl-dev \
    && curl -fsSL --retry 3 \
        "https://github.com/mongodb/mongo-php-driver/releases/download/${MONGODB_VERSION}/mongodb-${MONGODB_VERSION}.tgz" \
        -o /tmp/mongodb.tgz \
    && tar -xzf /tmp/mongodb.tgz -C /tmp \
    && cd /tmp/mongodb-${MONGODB_VERSION} \
    && phpize${PHP_VERSION} \
    && ./configure --with-php-config=/usr/bin/php-config${PHP_VERSION} \
    && make -j2 \
    && make install \
    && echo 'extension=mongodb.so' > /etc/php${PHP_VERSION}/conf.d/50_mongodb.ini \
    && cd / \
    && rm -rf /tmp/mongodb* \
    && apk del .mongodb-build-deps

# Provide version-independent `php` / `php-fpm` names, so the rest of the tooling
# (bin/container-console, the compose CMD) does not hardcode the PHP version.
RUN ln -s /usr/bin/php${PHP_VERSION} /usr/bin/php && \
    ln -s /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm

COPY override.ini /etc/php${PHP_VERSION}/conf.d/zz-override.ini

# We intentionally prefix the file with `zz-`, to make it load after other files.
# Files are loaded in alphabetical order. Since we'd like to override `www.conf`,
# we'd need to be sorted after it.
COPY override.conf /etc/php${PHP_VERSION}/php-fpm.d/zz-override.conf

# We install Nagios in the PHP image merely for configuration-validation purposes.
# The actual Nagios service that we use runs in a separate container.
RUN apk add --no-cache nagios

CMD ["/usr/sbin/php-fpm", "-F"]
