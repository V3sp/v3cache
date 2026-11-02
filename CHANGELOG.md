# Changelog

All notable changes to this project are documented here. This project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1] - 2026-11-02

### Fixed

- `ScriptInfo::$lastUsed` now reads `last_used_timestamp` from
  `opcache_get_status()` and falls back to a numeric `last_used` value. Previously
  the human-readable `last_used` string was cast to `0`, producing epoch dates.

## [0.2.0] - 2026-11-02

### Changed

- **BREAKING:** minimum supported PHP is now `8.3.36`. PHP 8.1 and 8.2 are no
  longer supported. PHP 8.1 is end-of-life and received no upstream fixes for
  `CVE-2026-109618` / `CVE-2026-109619`; PHP 8.2 reaches end of security
  support on 2026-12-31.
- CI/Docker matrix is now PHP 8.3.36, 8.4.27 and 8.5.12.
- Upgraded `predis/predis` to `^3.7` (v3.7.0), which includes the CRLF command
  smuggling fix and moves to the actively maintained 3.x line.

### Removed

- Support for PHP 8.1 and 8.2.

### Security

- Committed `composer.lock` for reproducible dependency installs; the lock file is
  excluded from Composer dist archives.
- Restricted Composer plugins to the dev-only CycloneDX SBOM plugin and install
  dependencies with `--no-scripts --no-plugins` in the Docker test environment.
- Added `composer audit --abandoned=fail` to the CI gate.
- Pinned Docker base images by digest, pinned the PECL Redis extension version,
  and pinned GitHub Actions by commit SHA.
- Added Dependabot, Gitleaks and Trivy image scanning. PHPStan remains the
  static-analysis gate; CodeQL does not support PHP.
- Added `SECURITY.md` with supported versions and private vulnerability reporting.
- Added CycloneDX SBOM generation in CI, a signed-tag/attestation release
  workflow, incident-response playbooks and a security release checklist.
- Added a scheduled `composer audit` job and documented monitoring/response
  expectations.
- Hardened config loading: stream wrappers rejected, 1 MiB size limit, PHP include
  isolated in a static scope, and `Application::createSafe()` / `ConfigLoaderFactory::createSafe()`
  available for JSON/YAML-only configs.
- Hardened Redis storage: TLS and SSL context options, `password_env`, connection
  and read/write timeouts, server-id validation, `SCAN` instead of `KEYS`, JSON
  depth/size limits, strict metrics validation and sanitized `StorageException`s.
- Added path guards for `warmup` and `invalidate`: files must be regular, non-symlink
  files under the current working directory or an explicit `--root`.
- Sanitized CLI output against terminal control/escape sequences and hid raw
  exception details unless `--verbose` is used.
- Added nested validation for Redis config, warmup paths, health thresholds,
  panel interval and `servers` keys.
- Added `Application::getConfigRedacted()` for safe config logging.
- OPcache timestamp/revalidate recommendations now warn that security patches
  require a worker restart.

## [0.1.1] - 2026-10-09

### Changed

- Excluded the root `Dockerfile` from Composer distribution archives; it remains in the Git repository for development and CI.
- Centralized default configuration loader setup for the application and CLI commands.

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
