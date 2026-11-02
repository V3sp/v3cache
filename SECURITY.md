# Security Policy

## Supported versions

| Version | PHP | Status |
|---|---|---|
| `0.2.x` | 8.3.36+ | Supported |
| `0.1.x` | 8.1+ | End-of-life; no security backports |

PHP 8.1 is end-of-life and did not receive upstream fixes for
`CVE-2026-109618` / `CVE-2026-109619`. PHP 8.2 reaches end of security support
on 2026-12-31. Use PHP 8.3.36+ (8.4.27+ / 8.5.12+ recommended).

## Reporting a vulnerability

Please **do not open a public issue** for security problems.

Use GitHub's private vulnerability reporting:

https://github.com/V3sp/v3cache/security/advisories/new

Include:

- affected version(s) and PHP version,
- a minimal reproduction or proof of concept,
- impact assessment (RCE, DoS, information disclosure, etc.),
- any suggested fix or mitigation.

We will acknowledge the report within **48 hours** and aim to triage it within
**7 days**. Please give us a reasonable window to release a fix before public
disclosure; we will credit reporters who want to be credited.

## Scope

In scope:

- the library code in `src/`,
- the CLI entry point `bin/opcache-manager`,
- configuration parsing and validation,
- Redis metrics storage integration,
- Docker/CI configuration in this repository.

Out of scope:

- vulnerabilities in PHP itself or in the host runtime,
- vulnerabilities in third-party dependencies (report them upstream; we will
  update once a patched release is available),
- issues in `v3cache-admin` or other consumer projects,
- misconfiguration of a consumer's Redis, PHP-FPM, web server or deployment.

## Security-related configuration notes

- **PHP config files are executable code.** `PhpConfigParser` uses `include`;
  never pass an untrusted or user-controlled path to `Application::create()`,
  `validate --config` or `warmup --config`. Prefer JSON/YAML for untrusted
  input, or use `Application::createSafe()` / `ConfigLoaderFactory::createSafe()`.
- **Redis**: use TLS and authentication when Redis is not on localhost. Do not
  expose Redis to untrusted networks.
- **OPcache**: `opcache.validate_timestamps=0` improves performance but means
  security patches are not picked up until the PHP worker is restarted. Always
  restart workers after deploying security fixes.
- **File paths**: `warmup` and `invalidate` only accept regular, non-symlink
  files under the current working directory or an explicit `--root`.

## SBOM and release provenance

The `Security` workflow generates a CycloneDX SBOM (`sbom` artifact) from
`composer.lock`. Locally:

```bash
docker compose run --rm ci composer cyclonedx:make-sbom \
  --output-format=JSON --output-file=sbom.json --omit=plugin \
  --output-reproducible --validate
```

Release tags should be signed and the `Release` workflow attests the source
archive. See `docs/security/release-checklist.md`.

## Monitoring

- Dependabot watches Composer, GitHub Actions and Docker.
- The `Security` workflow runs Gitleaks, Trivy and a scheduled `composer audit`
  every Monday.
- CI runs `composer audit --abandoned=fail` on every push and pull request.

## Incident response

See `docs/security/incident-response.md` for playbooks covering a compromised
Composer dependency, exposed Redis, vulnerable PHP runtime and leaked secrets.
