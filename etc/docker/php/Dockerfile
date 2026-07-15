FROM docker.io/alpine:3.23.5

ARG PHP_VERSION=85

WORKDIR /code

# Prebuilt PHP + extensions from Alpine's repositories — no compiling ext-mongodb
# from source (unlike the php:*-fpm base, which lacks it). Beyond PHP's always-on
# core, the application needs:
#   pecl-mongodb                    — the database driver (mongodb/mongodb library)
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
    php${PHP_VERSION}-pecl-mongodb \
    php${PHP_VERSION}-ctype \
    php${PHP_VERSION}-iconv \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-session \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-openssl \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-simplexml \
    php${PHP_VERSION}-xmlwriter \
    php${PHP_VERSION}-fileinfo \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-phar \
    php${PHP_VERSION}-tokenizer

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
