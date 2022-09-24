FROM composer:latest as composer
FROM php:7.4-fpm

LABEL vendor="Phalcon" \
      maintainer="Phalcon Team <team@phalcon.io>" \
      description="Phalcon Migrations working environment"

ENV PHALCON_VERSION="5.0.1" \
    PHP_VERSION="7.4"

ADD ./extra.ini /usr/local/etc/php/conf.d/

# Update
RUN apt update -y && \
    apt install -y \
        apt-utils \
        gettext \
        git \
        libpq-dev \
        libzip-dev \
        nano \
        sudo \
        wget \
        zip

# PECL Packages
RUN pecl install phalcon-${PHALCON_VERSION}  \
      xdebug

# Install PHP extensions
RUN docker-php-ext-install \
      gettext \
      pdo_mysql \
      pdo_pgsql \
      zip

# Install PHP extensions
RUN docker-php-ext-enable \
      opcache \
      phalcon \
      xdebug

# Cleanup
RUN apt autoremove -y \
    && apt autoclean -y \
    && apt clean -y \
    && rm -rf /tmp/* /var/tmp/* \
    && find /var/cache/apt/archives /var/lib/apt/lists /var/cache \
       -not -name lock \
       -type f \
       -delete \
    && find /var/log -type f | while read f; do echo -n '' > ${f}; done

# Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
# Bash script with helper aliases
COPY ./.bashrc /root/.bashrc
COPY ./.bashrc /home/phalcon/.bashrc

CMD ["php-fpm"]
