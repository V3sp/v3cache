<?php

declare(strict_types=1);

return [
    'storage' => [
        'type' => 'memory', // swap to 'redis' and fill the redis block for shared storage
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'db' => 0,
        ],
    ],
    'health' => [
        'min_hit_rate' => 80.0,
        'max_wasted_percent' => 20.0,
        'max_restarts' => 0,
        'max_memory_usage_percent' => 90.0,
    ],
    'optimization' => [
        'warmup_paths' => ['src/'],
    ],
];
