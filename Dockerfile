# Minimum supported PHP: 8.3.36 (security floor for CVE-2026-109618/109619).
# Compose passes pinned image references (tag + digest); do not use bare 8.3/8.4/8.5 here.
ARG PHP_IMAGE=php:8.5.12-cli@sha256:9236fd373d3bcda0659cf94838a744bd79791419292958963f8c6b34a0071554
FROM ${PHP_IMAGE}

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
    pecl install redis-6.3.0; \
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

COPY --from=composer:2.11.1@sha256:537fb03057ed901ae06bde7ef25af348903485ae1acc7fc789b80d8bac666f35 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["phpunit"]
