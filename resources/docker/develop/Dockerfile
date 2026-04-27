ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-fpm AS base

ARG PHP_VERSION=8.5
ARG UID=1000
ARG GID=1000
ARG USER=phalcon
ARG GROUP=phalcon

RUN set -eux \
# Add user and group \
    && groupadd -g "${GID}" "${GROUP}" \
    && useradd -l -m -u "${UID}" -g "${GID}" "${USER}" \
    && usermod -s /bin/bash "${USER}" \
    && mkdir -p /app \
    && chown "${USER}":"${GROUP}" /app \
    && chmod 0770 /app \
# Install needed packages \
    && apt update -y \
    && apt install -yq --no-install-recommends \
        apt-utils \
        curl \
        gettext \
        git \
        libpq-dev \
        libzip-dev \
        locales \
        nano \
        sudo \
        unzip \
        wget \
        zip \
    && sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
    && dpkg-reconfigure --frontend=noninteractive locales \
    && update-locale LANG=en_US.UTF-8 \
# Install PHP extensions \
    && docker-php-ext-install \
        gettext \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && mkdir -p /run/php \
    && chown "${USER}":"${GROUP}" /run/php \
    && chmod 0770 /run/php \
# Install PIE \
    && curl -fsSL https://github.com/php/pie/releases/latest/download/pie.phar -o /usr/local/bin/pie \
    && chmod +x /usr/local/bin/pie \
    && CFLAGS="-Wno-incompatible-pointer-types" pie install --no-interaction phalcon/cphalcon \
    && pie install --no-interaction xdebug/xdebug \
# Cleanup \
    && apt-get autoremove --purge -y \
    && apt-get autoclean -y \
    && apt-get clean -y \
    && find /var/cache/apt/archives /var/lib/apt/lists -not -name lock -type f -delete \
    && find /var/cache -type f -delete \
    && find /var/log -type f -delete

# hadolint ignore=DL3022
COPY --from=composer/composer:2 --chown=${USER}:${GROUP} --chmod=0770 /usr/bin/composer /usr/bin/composer
COPY resources/docker/develop/.bashrc /root/.bashrc
COPY resources/docker/develop/.bashrc /home/${USER}/.bashrc
COPY resources/docker/develop/extra.ini /usr/local/etc/php/conf.d/

ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US.UTF-8 \
    LC_ALL=en_US.UTF-8

SHELL ["/bin/bash", "--rcfile", "/home/${USER}/.bashrc", "-o", "pipefail", "-c"]

WORKDIR /app