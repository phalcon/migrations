# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-fpm

ARG UID=1000
ARG GID=1000
ARG USER=phalcon
ARG GROUP=phalcon

ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US.UTF-8 \
    LC_ALL=en_US.UTF-8 \
    COMPOSER_HOME=/app/.composer \
    COMPOSER_ALLOW_SUPERUSER=1

# hadolint ignore=DL3022
COPY --from=composer/composer:2 --chmod=0755 /usr/bin/composer /usr/bin/composer
COPY --chown=${UID}:${GID} resources/docker/develop/.bashrc /app/.bashrc
COPY resources/docker/develop/extra.ini /usr/local/etc/php/conf.d/extra.ini

SHELL [ "/bin/bash", "-o", "pipefail", "-c" ]

# PIE installs the PECL extensions. It is only available during this step.
# hadolint ignore=DL3008
RUN --mount=type=bind,from=ghcr.io/php/pie:1-bin,source=/pie,target=/usr/local/bin/pie \
    --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt/lists,sharing=locked <<EOT
set -eux
# Add user and group
groupadd -g "${GID}" "${GROUP}"
useradd -l -u "${UID}" -g "${GID}" -d /app "${USER}"
usermod -s /bin/bash "${USER}"
install -d -m 0770 -o "${USER}" -g "${GROUP}" /app
# Production php.ini
cp -p /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
# Dev utilities and libraries to build the extensions
apt-get update
apt-get install -yq --no-install-recommends \
    gettext \
    git \
    libonig-dev \
    libpq-dev \
    libtool \
    libzip-dev \
    locales \
    nano \
    sudo \
    unzip \
    wget \
    zip
# Dev/test extensions (PIE also enables each extension)
if php -r 'exit(PHP_VERSION_ID < 80600 ? 0 : 1);'; then
    pie install --no-cache xdebug/xdebug
else
    # Only an alpha of Xdebug supports PHP 8.6
    pie install --no-cache "xdebug/xdebug:^3.6@alpha"
fi
# Runtime extensions
docker-php-ext-install \
    gettext \
    pdo_mysql \
    pdo_pgsql \
    zip
# The PHP 8.6 image does not have mbstring (the older images have it for PECL)
if ! php -m | grep -qx mbstring; then
    docker-php-ext-install mbstring
fi
# Locales
sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG=en_US.UTF-8
# Unlimited memory for tests
echo 'memory_limit = -1' > /usr/local/etc/php/conf.d/zz-memory.ini
# Passwordless sudo for the phalcon group
echo "%${GROUP} ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/phalcon
chmod 0440 /etc/sudoers.d/phalcon
# Composer home owned by the user
mkdir -p "${COMPOSER_HOME}"
chown -R "${USER}:${GROUP}" "${COMPOSER_HOME}"
# Own the WORKDIR
mkdir -p /srv
chown "${USER}:${GROUP}" /srv
# Cleanup
rm -rf /tmp/* /var/tmp/*
EOT

WORKDIR /srv
USER ${USER}
