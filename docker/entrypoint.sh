#!/bin/sh
# Entry point dla kontenerów testowych.
#
# Każdy kontener ma własny wolumen vendor/, bo zależności muszą być rozwiązane
# dla jego wersji PHP. Na hoście (PHP 8.5) composer instaluje symfony 7.x
# (wymaga PHP >= 8.2), więc ten sam vendor nie działa w kontenerze 8.1.
#
# Kontrakt z planu (`docker compose run php81 vendor/bin/phpunit`) działa dzięki
# temu skryptowi: brak vendora → instalacja, potem wykonanie komendy.

set -e

cd /app

if [ ! -f vendor/autoload.php ]; then
    echo ">>> Brak vendor/autoload.php — instalacja zależności dla PHP $(php -r 'echo PHP_VERSION;')"
    # 'update', nie 'install': lock file jest lokalnym artefaktem (biblioteka go
    # nie commituje) i jest rozwiązany dla wersji PHP maszyny deweloperskiej.
    # Na hoście (PHP 8.5) dostajemy symfony 7.x (wymaga >= 8.2), więc instalacja
    # z tego samego locka w kontenerze 8.1 nie przejdzie. 'update' rozwiązuje
    # zależności dla aktualnej wersji PHP kontenera.
    composer update --no-interaction --prefer-dist --no-progress
fi

# Opcache w CLI jest kluczowy dla testów — weryfikujemy i wyjaśniamy problem.
if [ "$(php -r 'echo ini_get("opcache.enable_cli") ? "1" : "0";')" != "1" ]; then
    echo ">>> BŁĄD: opcache.enable_cli=0 — testy opcache nie zadziałają" >&2
    exit 1
fi

exec "$@"
