# v3sp/v3cache — AI usage reference

For AI agents consuming this package. Humans: README.md. Working on this repo: AGENTS.md.

## Meta
ns = V3Cache\OpcacheManager (-> src/). Every type below is ns-relative. !X = throws X.
pkg v3sp/v3cache (composer library); php ^8.1; ext-json, ext-zend-opcache; predis/predis (auto, redis storage only)
bin vendor/bin/opcache-manager; scope single-server, headless
test double Opcache\MockOpcacheApi

## Install
```bash
composer require v3sp/v3cache
```

## Usage
```php
use V3Cache\OpcacheManager\Opcache\NativeOpcacheApi;
use V3Cache\OpcacheManager\Service\OpcacheService;

$s = new OpcacheService(new NativeOpcacheApi());
$m = $s->getMetrics(); // !OpcacheNotEnabledException if opcache off
// $m->hitRate (%), $m->memoryUsage (MB), $m->cachedScripts
```

## API
Service\OpcacheService: new(Opcache\OpcacheApiInterface $api)
  getMetrics(): Model\OpcacheMetrics !Exception\OpcacheNotEnabledException
  getScripts(): Model\ScriptInfo[] !Exception\OpcacheNotEnabledException
  reload(): bool
  invalidate(string $path): bool
  compileFile(string $path): bool
  getSettings(): array // raw opcache_get_configuration(): directives, version, blacklist
  optimize(): Model\OptimizationRecommendation[] // recommendations only; never mutates

Opcache\OpcacheApiInterface: isEnabled(): bool; getStatus(bool $withScripts=false): ?array; reset(): bool; invalidate(string $path,bool $force=true): bool; compileFile(string $path): bool; getConfiguration(): array !Exception\OpcacheApiException
  impl Opcache\NativeOpcacheApi (prod); Opcache\MockOpcacheApi (tests): setEnabled(bool), setStatus(array), setConfiguration(array), getCalls(): array

Health\HealthChecker: new(Health\HealthThresholds $thresholds); static fromConfig(array): self; check(Model\OpcacheMetrics): Health\HealthResult
Health\HealthThresholds: new(float $minHitRate=80.0,float $maxWastedPercent=20.0,int $maxRestarts=0,float $maxMemoryUsagePercent=90.0); static fromConfig(array): self
Health\HealthResult: Health\HealthStatus $status; array<string,Health\CheckResult> $checks; string $summary; static fromArray(array): self
Health\CheckResult: string $name; Health\HealthStatus $status; float|int $actualValue; float|int $threshold; string $message
Health\HealthStatus: enum string{ok,warn,crit}
  check keys hitRate,memoryWasted,restarts,memoryUsage; overall=worst; CLI health-check exit 0/1/2=ok/warn/crit

Model\OpcacheMetrics: float memoryUsage,memoryFree,memoryWasted (MB); float hitRate (%); int cachedScripts,cachedKeys,maxCachedKeys,restarts,startTime; ?int lastRestartTime; Model\ScriptInfo[] scripts; static fromArray(array): self; toArray(): array // snake_case, symmetric
Model\ScriptInfo: string path; float memoryUsage; int hits,lastUsed,timestamp; fromArray/toArray
Model\OptimizationRecommendation: string directive; string|int|bool currentValue,recommendedValue; Health\HealthStatus severity; string description; fromArray

Application: static create(string $configPath): self; getConfig(): array; getMetricsStorage(): Contract\MetricsStorageInterface; getOpcacheService(): Service\OpcacheService; getHealthChecker(): Health\HealthChecker

Config\ConfigLoader: new(Config\ConfigParserRegistry,Config\ConfigValidator); load(string $path): array !Exception\ValidationException
  registry Config\ConfigParserRegistry([Config\{Php,Yaml,Json}ConfigParser])->for(path); schema Config\ConfigSchema

Contract\MetricsStorageInterface: save(string $serverId,Model\OpcacheMetrics): void; load(string): ?Model\OpcacheMetrics; loadAll(): array<string,Model\OpcacheMetrics>; delete(string): void
Storage\StorageFactory::create(array $config): Contract\MetricsStorageInterface // type memory(default)|redis; unknown -> !Exception\ValidationException
Storage\InMemoryMetricsStorage (per-process); Storage\RedisMetricsStorage(array $config) // key opcache:metrics:{id}, ttl 3600, json; cfg host 127.0.0.1, port 6379, password null, db 0

## Config file (all sections optional; storage default memory)
ext .php(returns array) | .json | .yaml/.yml (one nesting level only)
storage.type 'memory'|'redis'; storage.redis{host,port,password,db}
health{min_hit_rate 80.0, max_wasted_percent 20.0, max_restarts 0, max_memory_usage_percent 90.0}
optimization.warmup_paths string[] (warmup fallback)
reserved, validated but unused: servers (open map id=>endpoint, key timeout); panel.poll_interval int 5
bad config -> !Exception\ValidationException; getErrors(): array<string,string>

## CLI (vendor/bin/opcache-manager <cmd> [opts])
status [--format=table|json] -> 1 if disabled
reload | clear | optimize -> 0
invalidate <path> -> 1 if missing
warmup [--file=<list>|--dir=<dir>|--config=<path>] -> uses optimization.warmup_paths fallback; 0
health-check [--format=table|json|nagios] -> 0/1/2 = ok/warn/crit
doctor -> 1 if disabled
validate [--config=<path>] -> 1 on fail

## Exceptions (ns\Exception, all extend \RuntimeException)
OpcacheNotEnabledException: getMetrics/getScripts while off
OpcacheApiException: opcache_get_configuration() returns false
ValidationException: bad config; getErrors(): array<string,string>
StorageException: reserved for custom MetricsStorageInterface impls (bundled backends don't throw)

## Gotchas
opcache.enable & opcache.enable_cli are PHP_INI_SYSTEM; ini_set() no-op; in CLI set opcache.enable_cli=1 or getStatus() returns null
optimize() returns recommendations only; most directives are not settable at runtime
Model toArray()/fromArray() use snake_case; keep them symmetric
YAML supports one nesting level; nested storage.redis needs php array or json
Application::create() needs an existing valid config file, but every section may be omitted

## Tasks (intent -> call)
get metrics: (new Service\OpcacheService(new Opcache\NativeOpcacheApi()))->getMetrics()
get scripts: $s->getScripts()
health: (new Health\HealthChecker(new Health\HealthThresholds()))->check($m) // ->status === Health\HealthStatus::OK
persist metrics: Application::create('config.php')->getMetricsStorage()->save('web-1',$m) // storage.type=redis
reload / clear: $s->reload()  |  CLI reload  |  CLI clear
invalidate one file: $s->invalidate($path)  |  CLI invalidate <path>
optimize: $s->optimize()  |  CLI optimize
warm a directory: CLI warmup --dir=src/  |  loop: foreach php file -> $s->compileFile($f)
load config programmatically: Application::create($path)  |  Config\ConfigLoader::load($path)
diagnose / validate env: CLI doctor  |  CLI validate [--config=]

## More
README.md (human overview); examples/config.{php,yaml,json}; src/ (implementation); tests/ (behaviour examples)
