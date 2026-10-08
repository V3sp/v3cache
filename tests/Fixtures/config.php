<?php

return [
    'storage' => [
        'type' => 'memory',
    ],
    'health' => [
        'min_hit_rate' => 80.0,
        'max_wasted_percent' => 20.0,
        'max_restarts' => 0,
        'max_memory_usage_percent' => 90.0,
    ],
    'servers' => [],
    'optimization' => [
        'warmup_paths' => ['src/'],
    ],
    'panel' => [
        'poll_interval' => 5,
    ],
];
