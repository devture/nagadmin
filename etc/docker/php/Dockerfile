ARG PHP_VERSION=85

FROM docker.io/alpine:3.24.4 AS base

ARG PHP_VERSION
# The minimum version of Alpine's PHP packages. Alpine ships PHP releases into its stable branch without a new
# Alpine release, and a rebuild would reuse the cached `apk add` layer below: bumping this (Renovate does, from
# Alpine's package index) rebuilds that layer, refreshing every package in it, and fails the build if the mirror
# doesn't have the version yet.
ARG PHP_PACKAGE_VERSION="8.5.12-r0"
# renovate: datasource=github-releases depName=mongodb/mongo-php-driver
ARG MONGODB_VERSION=2.5.8

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
    "php${PHP_VERSION}>=${PHP_PACKAGE_VERSION}" \
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
    && curl -fsSL --retry 10 --retry-delay 15 --retry-max-time 300 \
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
RUN ln -sf /usr/bin/php${PHP_VERSION} /usr/bin/php && \
    ln -sf /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm

# Composer, on the image's PHP and extensions (bin/composer builds this stage, the GitHub Check through it).
# It adds what Composer itself needs: git and unzip.
FROM base AS composer

# renovate: datasource=github-releases depName=composer/composer
ARG COMPOSER_VERSION=2.11.2

RUN apk add --no-cache git unzip

# A RUN, not an ADD: BuildKit revalidates a remote ADD with the server on every build, so even a fully cached build
# would fail while getcomposer.org is down. This layer is cached by its command, which carries the version.
RUN wget -q -O /usr/local/bin/composer "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar" && \
    chmod 755 /usr/local/bin/composer

# The production image: the last stage, so building without a target gives it.
FROM base AS production

ARG PHP_VERSION

COPY override.ini /etc/php${PHP_VERSION}/conf.d/zz-override.ini

# We intentionally prefix the file with `zz-`, to make it load after other files.
# Files are loaded in alphabetical order. Since we'd like to override `www.conf`,
# we'd need to be sorted after it.
COPY override.conf /etc/php${PHP_VERSION}/php-fpm.d/zz-override.conf

# We install Nagios in the PHP image merely for configuration-validation purposes.
# The actual Nagios service that we use runs in a separate container.
RUN apk add --no-cache nagios

CMD ["/usr/sbin/php-fpm", "-F"]
