# v3sp/v3cache

Monitoring, health checking, warmup and optimization for PHP OPcache. A headless
Composer library for PHP 8.1+ — no GUI and no server orchestration. Multi-server
aggregation and dashboards live in a separate project that consumes this library.

## Requirements

- PHP `^8.1`
- `ext-json`
- `ext-zend-opcache`
- `predis/predis` (installed automatically; used by the Redis storage backend)

## Installation

```bash
composer require v3sp/v3cache
```

## Usage

```php
<?php

use V3Cache\OpcacheManager\Opcache\NativeOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

$service = new OpcacheService(new NativeOpcacheApi());

$metrics = $service->getMetrics();      // V3Cache\OpcacheManager\Model\OpcacheMetrics
echo $metrics->hitRate;                  // e.g. 95.2
echo $metrics->memoryUsage;              // MB
```

`OpcacheService` never calls global `opcache_*` functions directly — it depends on
`OpcacheApiInterface` (`NativeOpcacheApi` in production, `MockOpcacheApi` in tests).

### Bootstrap / facade

`Application` loads a config file and wires the common dependencies:

```php
use V3Cache\OpcacheManager\Application;

$app = Application::create(__DIR__ . '/config.php');

$app->getConfig();          // array<string, mixed>
$app->getMetricsStorage();  // MetricsStorageInterface
$app->getOpcacheService();  // OpcacheService
$app->getHealthChecker();   // HealthChecker
```

### Health checking

```php
use V3Cache\OpcacheManager\Health\HealthChecker;
use V3Cache\OpcacheManager\Health\HealthStatus;

$result = (new HealthChecker())->check($metrics); // uses default thresholds

if ($result->status === HealthStatus::WARN) {
    echo $result->summary;
}
```

`HealthStatus` is `ok` / `warn` / `crit`. The worst individual check determines the
overall status.

## Configuration

Three formats are supported, selected by file extension: `.php`, `.yaml` / `.yml`,
`.json`. Examples live in [`examples/`](examples). Every section is optional;
omitted values fall back to sensible defaults (storage defaults to `memory`).

```php
<?php

return [
    'storage' => [
        'type' => 'memory', // 'memory' or 'redis'
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
```

| Section | Keys | Used by |
| --- | --- | --- |
| `storage` | `type` (`memory`/`redis`, optional, default `memory`), `redis.{host,port,password,db}` | `StorageFactory` |
| `health` | `min_hit_rate`, `max_wasted_percent`, `max_restarts`, `max_memory_usage_percent` | `HealthChecker` |
| `optimization` | `warmup_paths` | `warmup` command fallback |
| `servers` | open section (`id => endpoint`) + reserved `timeout` | consumers / tooling |
| `panel` | `poll_interval` | consumers / tooling |

> YAML parsing supports a single level of nesting; use a PHP array or JSON for the
> nested `storage.redis` block.

## Storage

`MetricsStorageInterface` persists per-server `OpcacheMetrics`:

- `memory` — `InMemoryMetricsStorage` (per-process, zero dependencies).
- `redis` — `RedisMetricsStorage` via predis, key `opcache:metrics:{serverId}`,
  TTL 3600s, JSON encoded.

## CLI

```bash
vendor/bin/opcache-manager <command> [options]
```

| Command | Description |
| --- | --- |
| `status [--format=table\|json]` | Show OPcache status |
| `reload` | Reload OPcache |
| `clear` | Clear OPcache |
| `optimize` | Show optimization recommendations |
| `invalidate <path>` | Invalidate a single script |
| `warmup [--file=... \| --dir=...] [--config=...]` | Compile files into OPcache |
| `health-check [--format=table\|json\|nagios]` | Check health; exit `0`/`1`/`2` = OK/WARN/CRIT |
| `doctor` | Diagnose the OPcache environment |
| `validate [--config=...]` | Validate configuration |

```bash
vendor/bin/opcache-manager status --format=json
vendor/bin/opcache-manager warmup --dir=src/
vendor/bin/opcache-manager health-check --format=nagios
```

## Development

Everything runs in Docker (local PHP usually lacks `opcache.enable_cli=1`):

```bash
docker compose run php85 vendor/bin/phpunit --testsuite=unit   # unit tests
docker compose run php85 vendor/bin/phpunit                    # all tests
docker compose run ci                                           # phpstan + phpcs + phpunit (with Redis)
docker compose run ci vendor/bin/phpstan analyse
docker compose run ci vendor/bin/phpcs
```

## License

MIT. See [LICENSE](LICENSE).
