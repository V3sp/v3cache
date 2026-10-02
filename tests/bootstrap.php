<?php

declare(strict_types=1);

$autoloaders = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (file_exists($autoloader)) {
        require $autoloader;

        return;
    }
}

fwrite(STDERR, 'Nie znaleziono autoloadera. Uruchom `composer install`.' . PHP_EOL);
exit(1);
