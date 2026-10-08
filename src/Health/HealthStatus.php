<?php

declare(strict_types=1);

namespace V3Cache\OpcacheManager\Health;

enum HealthStatus: string
{
    case OK = 'ok';
    case WARN = 'warn';
    case CRIT = 'crit';
}
