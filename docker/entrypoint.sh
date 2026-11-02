#!/bin/sh
# Entry point dla kontenerów testowych.
#
# Każdy kontener ma własny wolumen vendor/, bo zależności muszą być rozwiązane
# dla jego wersji PHP. Vendor rozwiązany na hoście nie zawsze pasuje do
# kontenera z inną wersją PHP.
#
# Kontrakt z planu (`docker compose run php83 vendor/bin/phpunit`) działa dzięki
# temu skryptowi: brak vendora → instalacja, potem wykonanie komendy.

set -e

cd /app

if [ ! -f vendor/autoload.php ]; then
    echo ">>> Brak vendor/autoload.php — instalacja zależności dla PHP $(php -r 'echo PHP_VERSION;')"
    if [ -f composer.lock ]; then
        # Lockfile jest commitowany: instalujemy dokładnie rozwiązany zestaw.
        # --no-scripts/--no-plugins: nie wykonujemy kodu z pakietów przy instalacji.
        composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-plugins
    else
        # Fallback dla świeżego checkoutu bez lockfile.
        composer update --no-interaction --prefer-dist --no-progress --no-scripts --no-plugins
    fi
fi

# Opcache w CLI jest kluczowy dla testów — weryfikujemy i wyjaśniamy problem.
if [ "$(php -r 'echo ini_get("opcache.enable_cli") ? "1" : "0";')" != "1" ]; then
    echo ">>> BŁĄD: opcache.enable_cli=0 — testy opcache nie zadziałają" >&2
    exit 1
fi

exec "$@"
