# Repository Guidelines

Composer library `v3sp/v3cache`: monitoring, health checks, warmup and optimization
for PHP OPcache. PHP `^8.3.36`. Headless — no GUI and no server orchestration;
multi-server aggregation and dashboards live in a separate private project
(`v3cache-admin`) that depends on this package.

## Run everything in Docker — never with local PHP

Local PHP (currently 8.5.12; minimum supported is 8.3.36) does not match the tested matrix and usually lacks
`opcache.enable_cli=1`, so OPcache integration tests silently skip. This is a
hard repo convention (see `context/foundation/lessons.md`).

- Unit tests: `docker compose run php85 vendor/bin/phpunit --testsuite=unit`
- All tests on one version: `docker compose run php85 vendor/bin/phpunit`
- Single test file: `docker compose run php85 vendor/bin/phpunit tests/Unit/Service/OpcacheServiceTest.php`
- Single test: `docker compose run php85 vendor/bin/phpunit --filter testGetMetrics`
- Full CI gate (audit -> phpstan -> phpcs -> phpunit, with Redis): `docker compose run ci`
- Static analysis: `docker compose run ci vendor/bin/phpstan analyse`
- Lint: `docker compose run ci vendor/bin/phpcs`
- Composer validation: `docker compose run --rm ci composer validate --strict`
- Dependency audit: `docker compose run --rm ci composer audit --abandoned=fail`
- Platform check: `docker compose run --rm php83 composer check-platform-reqs`
- SBOM: `docker compose run --rm ci composer cyclonedx:make-sbom --output-format=JSON --output-file=sbom.json --omit=plugin --output-reproducible --validate`
- Security workflow: `.github/workflows/security.yml` (Gitleaks, Trivy, SBOM, scheduled audit)
- Incident playbooks: `docs/security/incident-response.md`
- Release checklist: `docs/security/release-checklist.md`
- Other PHP versions: `php83`/`php84`/`php85` services (minimum 8.3.36).

`docker/entrypoint.sh` runs `composer install` from the committed `composer.lock`
when a container's `vendor/` is missing (fallback: `composer update`), so the
first `run` is slow. Each PHP version has its own `vendor-phpNN` volume; host
`vendor/` is not reused. Changing `build.args.PHP_IMAGE` requires
`docker compose build` — `run` otherwise reuses a stale image.

## Testing quirks

- Suites: `unit` (`tests/Unit`) and `integration` (`tests/Integration`). A plain
  `phpunit` run executes both; integration tests `markTestSkipped()` when OPcache
  is unavailable.
- `phpunit.xml` is strict: `failOnRisky`, `failOnWarning`,
  `beStrictAboutOutputDuringTests`. Commands must not echo directly — use
  `OutputFormatter` and return the exit code from `execute()`.
- `opcache.enable` / `opcache.enable_cli` are `PHP_INI_SYSTEM`; `ini_set()` and
  `<ini>` in `phpunit.xml` do nothing. They are set only in `Dockerfile`
  (`zz-opcache-manager.ini`).
- Redis-backed tests (`tests/Integration/Storage/RedisMetricsStorageTest.php`)
  need the `redis` service; `ci` depends on it. They skip when Redis is unreachable.

## Architecture

- PSR-4 `V3Cache\OpcacheManager\` -> `src/`; tests `...\Tests\` -> `tests/`.
- `src/Application.php` is the library bootstrap/facade (`Application::create($configPath)`):
  exposes `getConfig()`, `getMetricsStorage()`, `getOpcacheService()`, `getHealthChecker()`.
- CLI is a hand-rolled framework (`src/Cli`), not Symfony Console. Add commands by
  implementing `CommandInterface` (`name`, `description`, `execute(array $args, array $options): int`)
  and registering in `Cli\ApplicationFactory`. Entry point: `bin/opcache-manager`.
  The framework is a public extension point for consumer projects.
- Business logic lives in `OpcacheService`, which depends on `OpcacheApiInterface`.
  Unit tests inject `MockOpcacheApi`; never call global `opcache_*` functions directly.
- Config: three formats (PHP array / YAML / JSON) via `ConfigParserRegistry`;
  YAML uses the repo's own `SimpleYamlParser` (single level of nesting only —
  nested blocks like `storage.redis` need PHP array or JSON), validation is
  schema-driven (`ConfigSchema`). `servers` is an "open" section (dynamic
  `id => endpoint` keys), reserved for consumer tooling; `panel` is likewise reserved.
- Storage: `storage.type` is optional and defaults to `memory`; `redis`
  (`RedisMetricsStorage` via `predis/predis`, key `opcache:metrics:{serverId}`,
  TTL 3600, JSON via `OpcacheMetrics::toArray()`) is the alternative. The library
  exposes storage as a capability; it has no internal consumer.
- `OpcacheMetrics`/`ScriptInfo` have `fromArray()`/`toArray()` (snake_case) — keep them symmetric.
- Domain exceptions live in `src/Exception` and extend `RuntimeException`.

## Do not add

- `symfony/console` or `symfony/yaml` — both were deliberately removed in favor of
  the in-repo CLI framework and YAML parser.
- `vendor/bin/*` invocations with local PHP (see above).

## Notes

- `README.md`, `examples/`, `CHANGELOG.md`, `.gitattributes` and
  `.github/workflows/ci.yml` exist (publication prep).
- CLI loads config only in the commands that need it (`validate`, `warmup`),
  not globally in `ApplicationFactory`.
- Progress and detailed spec: `context/changes/opcache-manager/plan.md`.
  `context/` and `.opencode/` are gitignored local tooling, not part of the library.
- `composer.lock` is committed for reproducible dev installs and is excluded from
  Composer dist archives via `.gitattributes`. Do not commit `vendor/`.
- Composer plugins are blocked except the dev-only
  `cyclonedx/cyclonedx-php-composer` plugin used to generate the SBOM.
- No composer scripts; invoke tools directly.
- PHPStan level 8, analyzes `src/` only. PHPCS PSR-12, 120-char line limit,
  lints `src/`, `tests/`, `bin/`.
