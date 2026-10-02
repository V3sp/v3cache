ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        libzip-dev \
        unzip; \
    rm -rf /var/lib/apt/lists/*

# zip (wymagane przez composer) + peclowy redis
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" zip; \
    pecl install redis; \
    docker-php-ext-enable redis

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["phpunit"]
