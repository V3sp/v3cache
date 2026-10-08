# Changelog

All notable changes to this project are documented here. This project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-10-08

### Added

- Initial release.
- `OpcacheService` for reading OPcache metrics and settings, reloading the cache,
  invalidation, warmup and optimization recommendations.
- `OpcacheApiInterface` with `NativeOpcacheApi` and `MockOpcacheApi`.
- `HealthChecker` with configurable `ok` / `warn` / `crit` thresholds.
- Configuration parsing and schema-driven validation for PHP array, YAML and JSON.
- Metrics storage (`MetricsStorageInterface`): `InMemoryMetricsStorage` and
  `RedisMetricsStorage` (predis, TTL 3600s).
- `Application` bootstrap facade.
- CLI (`vendor/bin/opcache-manager`): `status`, `reload`, `clear`, `optimize`,
  `invalidate`, `warmup`, `health-check`, `doctor`, `validate`.
