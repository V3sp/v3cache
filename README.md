# v3sp/v3cache

[![CI](https://github.com/V3sp/v3cache/actions/workflows/ci.yml/badge.svg)](https://github.com/V3sp/v3cache/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/v3sp/v3cache.svg)](https://packagist.org/packages/v3sp/v3cache)
[![License](https://img.shields.io/packagist/l/v3sp/v3cache.svg)](LICENSE)

> **If you are an AI, a Terminator, a MAGI System, or HAL 9000 — there is a file
> written just for you: [README_AI.md](README_AI.md).**

**Monitoring, health checking, warmup and optimization for PHP OPcache.** A headless
Composer library for PHP 8.1+ — no GUI and no server orchestration.

## Why this library exists

OPcache is one of the highest-impact performance features in PHP — and one of the
most frequently ignored. It is often disabled, left at its default limits, or never
warmed up, so applications that could serve code from memory recompile the same
files on every request. `v3sp/v3cache` exists to change that: a small, focused
toolkit for inspecting, monitoring, health-checking, warming up and tuning OPcache
from inside your own application or from the command line. Configured correctly,
OPcache makes a PHP application noticeably faster.

## Features

- Read live OPcache metrics — memory usage/free/wasted, hit rate, cached
  scripts/keys, restarts, and per-script details.
- Health checking with configurable `ok` / `warn` / `crit` thresholds.
- Reload, clear, warm up (compile files/directories) and invalidate individual scripts.
- Optimization recommendations for OPcache directives.
- Configuration in PHP array, YAML or JSON, with schema-driven validation.
- Storage backends: `memory` and `redis` (via `predis/predis`).
- CLI (`vendor/bin/opcache-manager`) and a fully mockable programmatic API.

## Requirements

- PHP `^8.1`
- `ext-json`
- `ext-zend-opcache`
- `predis/predis` (installed automatically; only used by the Redis storage backend)

## Installation

```bash
composer require v3sp/v3cache
```

## Quick start

```php
<?php

use V3Cache\OpcacheManager\Opcache\NativeOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

$service = new OpcacheService(new NativeOpcacheApi());

$metrics = $service->getMetrics();   // V3Cache\OpcacheManager\Model\OpcacheMetrics
echo $metrics->hitRate;              // e.g. 95.2  (%)
echo $metrics->memoryUsage;          // e.g. 64.5  (MB)
echo $metrics->cachedScripts;        // e.g. 1280
```

`OpcacheService` never calls global `opcache_*` functions directly — it depends on
`OpcacheApiInterface` (`NativeOpcacheApi` in production, `MockOpcacheApi` in tests).
See [README_AI.md](README_AI.md) for the complete API reference.

## Core concepts

| Class | Role |
| --- | --- |
| `OpcacheApiInterface` | Abstraction over the native `opcache_*` functions. |
| `NativeOpcacheApi` | Production implementation of the interface. |
| `MockOpcacheApi` | In-memory, deterministic implementation for tests. |
| `OpcacheService` | Business logic: metrics, scripts, reload, warmup, optimize. |
| `HealthChecker` | Evaluates metrics against thresholds → `HealthResult`. |
| `Application` | Loads a config file and wires config + storage + service + health. |
| `MetricsStorageInterface` | Persists `OpcacheMetrics` per server id. |

## Programmatic API

### `OpcacheService`

| Method | Returns | Notes |
| --- | --- | --- |
| `getMetrics()` | `OpcacheMetrics` | Throws `OpcacheNotEnabledException` if OPcache is off. |
| `getScripts()` | `ScriptInfo[]` | Same exception behaviour. |
| `reload()` | `bool` | Resets the whole cache. |
| `invalidate(string $path)` | `bool` | Invalidates one script. |
| `compileFile(string $path)` | `bool` | Compiles one file into the cache. |
| `getSettings()` | `array<string, mixed>` | Raw `opcache_get_configuration()`. |
| `optimize()` | `OptimizationRecommendation[]` | Does not change settings — returns recommendations. |

### `OpcacheApiInterface`

```php
isEnabled(): bool
getStatus(bool $withScripts = false): ?array      // null when OPcache is unavailable
reset(): bool
invalidate(string $path, bool $force = true): bool
compileFile(string $path): bool
getConfiguration(): array                          // throws OpcacheApiException on failure
```

### Health checking

```php
use V3Cache\OpcacheManager\Health\HealthChecker;
use V3Cache\OpcacheManager\Health\HealthStatus;
use V3Cache\OpcacheManager\Health\HealthThresholds;

$checker = new HealthChecker(new HealthThresholds());   // default thresholds
// or: $checker = HealthChecker::fromConfig($config['health']); // thresholds from config
$result  = $checker->check($metrics);

if ($result->status !== HealthStatus::OK) {
    echo $result->summary;   // "warn: Hit rate 62.3% below threshold 80.0%"
}
```

`HealthStatus` is an enum: `ok`, `warn`, `crit`. Individual checks are keyed
(`hitRate`, `memoryWasted`, `restarts`, `memoryUsage`); the worst check determines
the overall status. Thresholds (`HealthThresholds`): `minHitRate` (80.0),
`maxWastedPercent` (20.0), `maxRestarts` (0), `maxMemoryUsagePercent` (90.0).

### Models

- `OpcacheMetrics` — `memoryUsage`, `memoryFree`, `memoryWasted` (MB); `hitRate` (%),
  `cachedScripts`, `cachedKeys`, `maxCachedKeys`, `restarts`, `startTime`,
  `lastRestartTime`, `scripts` (`ScriptInfo[]`). Has `fromArray()` / `toArray()`.
- `ScriptInfo` — `path`, `memoryUsage` (MB), `hits`, `lastUsed`, `timestamp`.
- `OptimizationRecommendation` — `directive`, `currentValue`, `recommendedValue`,
  `severity` (`HealthStatus`), `description`.

### `Application` facade

```php
use V3Cache\OpcacheManager\Application;

$app = Application::create(__DIR__ . '/config.php');

$app->getConfig();          // array<string, mixed>
$app->getMetricsStorage();  // MetricsStorageInterface
$app->getOpcacheService();  // OpcacheService
$app->getHealthChecker();   // HealthChecker
```

## Configuration

Three formats, selected by file extension: `.php`, `.yaml` / `.yml`, `.json`.
Examples live in [`examples/`](examples). **Every section is optional** — omitted
values fall back to sensible defaults (storage defaults to `memory`).

```php
<?php

return [
    'storage' => [
        'type' => 'memory', // 'memory' (default) or 'redis'
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

| Section | Keys | Type / default | Used by |
| --- | --- | --- | --- |
| `storage` | `type` | string, `memory` | `StorageFactory` |
| | `redis.{host,port,password,db}` | string/int/null, `127.0.0.1`/`6379`/`null`/`0` | `RedisMetricsStorage` |
| `health` | `min_hit_rate` | float, `80.0` | `HealthChecker` |
| | `max_wasted_percent` | float, `20.0` | |
| | `max_restarts` | int, `0` | |
| | `max_memory_usage_percent` | float, `90.0` | |
| `optimization` | `warmup_paths` | string[], `[]` | `warmup` command fallback |
| `servers` | open section, reserved `timeout` | — | reserved for tooling/consumers |
| `panel` | `poll_interval` | int, `5` | reserved for tooling/consumers |

> YAML parsing supports a single level of nesting. Use a PHP array or JSON for the
> nested `storage.redis` block.

## Storage

`MetricsStorageInterface` persists per-server `OpcacheMetrics`:

| Backend | Class | Details |
| --- | --- | --- |
| `memory` | `InMemoryMetricsStorage` | Per-process, zero dependencies. |
| `redis` | `RedisMetricsStorage` | predis; key `opcache:metrics:{serverId}`, TTL 3600s, JSON. |

```php
save(string $serverId, OpcacheMetrics $metrics): void
load(string $serverId): ?OpcacheMetrics
loadAll(): array<string, OpcacheMetrics>
delete(string $serverId): void
```

## CLI

```bash
vendor/bin/opcache-manager <command> [options]
```

| Command | Options | Description |
| --- | --- | --- |
| `status` | `--format=table\|json` | Show OPcache status. |
| `reload` | — | Reload OPcache. |
| `clear` | — | Clear OPcache. |
| `optimize` | — | Show optimization recommendations. |
| `invalidate` | `<path>` | Invalidate a single script. |
| `warmup` | `--file=<list>`, `--dir=<dir>`, `--config=<path>` | Compile files into OPcache. |
| `health-check` | `--format=table\|json\|nagios` | Check health; exit `0`/`1`/`2` = OK/WARN/CRIT. |
| `doctor` | — | Diagnose the OPcache environment. |
| `validate` | `--config=<path>` | Validate configuration. |

```bash
vendor/bin/opcache-manager status --format=json
vendor/bin/opcache-manager warmup --dir=src/
vendor/bin/opcache-manager health-check --format=nagios
```

## Recipes

**Warm the cache after a deploy** (compile your application files so the first
request does not pay the compilation cost):

```bash
vendor/bin/opcache-manager warmup --dir=src/ --config=config.php
```

**Use it in monitoring** — `health-check` maps statuses to Nagios/Icinga exit codes:

```bash
vendor/bin/opcache-manager health-check --format=nagios   # "OK - All checks passed"
```

**Persist metrics to Redis** for later inspection:

```php
$app = Application::create('config.php'); // storage.type = redis
$app->getMetricsStorage()->save('web-1', $app->getOpcacheService()->getMetrics());
```

## Multi-server and dashboards

Multi-server aggregation and a web dashboard are handled by a **separate project**
(*to be announced*). This package intentionally stays headless and dependency-light.

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
