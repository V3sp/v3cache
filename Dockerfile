# Minimum supported PHP: 8.3.36 (security floor for CVE-2026-109618/109619).
# Compose passes pinned image references (tag + digest); do not use bare 8.3/8.4/8.5 here.
ARG PHP_IMAGE=php:8.6.0-cli@sha256:d84acf3155d907afaa5afa9b9457c5f12016b6dc7ade84659f40cdc505cc087d
FROM ${PHP_IMAGE}

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        libonig-dev \
        libzip-dev \
        unzip; \
    rm -rf /var/lib/apt/lists/*; \
    git config --global --add safe.directory /app

# zip (wymagane przez composer) - redis jest dostarczany przez predis/predis (czyste PHP)
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" mbstring zip; \
    rm -rf /tmp/redis-src /tmp/redis.tgz

# opcache jest już wkompilowany w oficjalne obrazy php — trzeba go tylko włączyć.
# opcache.enable_cli=1 jest KRYTYCZNE: bez niego opcache_get_status() w CLI
# zwraca false i Phase 2 nie ma czego testować.
RUN set -eux; \
    { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'memory_limit=512M'; \
    } > /usr/local/etc/php/conf.d/zz-opcache-manager.ini

COPY --from=composer:2.11.1@sha256:537fb03057ed901ae06bde7ef25af348903485ae1acc7fc789b80d8bac666f35 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["phpunit"]
