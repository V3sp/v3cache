# Incident Response Runbook

This runbook covers security incidents that can affect `v3sp/v3cache` consumers.
It is intentionally short and action-oriented.

## Roles

- **Incident lead** — owns the timeline, decisions and communication.
- **Maintainer** — patches the library, cuts a release, updates Packagist.
- **Consumer contact** — coordinates upgrades/restarts in the affected project.
- **Security reviewer** — validates the fix and the disclosure text.

For a single-maintainer project, one person may hold several roles; record that
in the incident timeline.

## Detection sources

- Dependabot alerts and update PRs.
- Weekly `Security` workflow: Gitleaks, Trivy, scheduled `composer audit`.
- `composer audit --abandoned=fail` in CI.
- User reports via GitHub private vulnerability reporting.
- Upstream advisories (PHP, Packagist, Predis, PHPUnit, PHPCS).

## Severity and targets

| Severity | Examples | Acknowledge | Mitigate/release |
|---|---|---|---|
| Critical | RCE, active exploitation, leaked production credentials | 4 h | 24–48 h |
| High | Auth bypass, credential exposure, known exploited dependency | 24 h | 7 days |
| Medium | DoS, limited information disclosure | 48 h | 30 days |
| Low | Hardening, defense-in-depth | 5 days | next release |

## Playbook 1 — compromised Composer dependency

Signals: unexpected `composer.lock` change, new Composer plugin, suspicious
`autoload.files`, Dependabot/security advisory, Packagist malware flag.

1. **Stop deploys** that install the affected lock/version.
2. Identify the package, version and source reference in `composer.lock`.
3. Check `vendor/<vendor>/<package>/composer.json` for:
   - `type: composer-plugin`,
   - `scripts` (`post-install-cmd`, `post-update-cmd`),
   - `autoload.files` entries that execute on every request.
4. Treat the host/CI runner as compromised if the package executed:
   - isolate it from the network,
   - preserve logs and disk snapshots,
   - rotate credentials that the process could read (GitHub, Packagist,
     cloud, SSH, database, Redis, CI secrets).
5. Rebuild from a clean checkout and a reviewed lock file:
   ```bash
   docker compose down -v
   docker compose run --rm --entrypoint composer php83 install \
     --no-interaction --prefer-dist --no-scripts --no-plugins
   ```
6. Run `docker compose run --rm ci` and `composer audit --abandoned=fail`.
7. Report the package/version to Packagist and the upstream maintainer.
8. Publish a patched release if the library itself was affected.

## Playbook 2 — exposed or compromised Redis

1. Remove public exposure immediately; bind to localhost or a private network.
2. Require authentication and TLS:
   - `storage.redis.tls: true`,
   - `storage.redis.password_env: REDIS_PASSWORD` (preferred over a literal),
   - `storage.redis.ssl` for CA/peer verification.
3. Inspect keys under `opcache:metrics:*`; delete unknown keys.
4. If the instance may have been writable by an attacker, treat it as
   compromised: rotate credentials, rebuild the instance, and review network
   logs for `CONFIG`, `SAVE`, `MODULE`, `ACL` or `FLUSHALL`.
5. Verify the application only reads/writes the expected key prefix.

## Playbook 3 — vulnerable PHP runtime

1. Identify the PHP patch level in every environment (`php -v`).
2. Update to the current patched release for the supported branch
   (8.3.36+, 8.4.27+, 8.5.12+ as of this runbook).
3. Rebuild Docker images and restart PHP-FPM/Apache/workers. A CLI update alone
   does not patch a running FPM pool.
4. If an immediate update is impossible, apply the mitigations from the PHP
   advisory (for example `memory_limit=-1`, `post_max_size`,
   `session.upload_progress.enabled=0`, `fatal_error_backtraces=0` on 8.5) and
   treat the environment as at risk until patched.
5. Confirm with `php -v`, `php -i | grep opcache`, and application health checks.

## Playbook 4 — leaked secret

1. Revoke/rotate the secret first; do not wait for cleanup.
2. Identify where it was exposed (commit, log, artifact, CI output).
3. Remove it from the current tree; if it is in git history, coordinate a
   history rewrite with all consumers before force-pushing.
4. Add a regression check (Gitleaks rule, test, CI guard).
5. Record the rotation in the incident timeline.

## Evidence and communication

- Preserve logs, lock files, SBOMs and container image digests before cleanup.
- Use a private channel; do not disclose exploit details before a fix is
  available.
- Publish a GitHub Security Advisory for library vulnerabilities.
- After resolution, write a short postmortem: timeline, root cause, impact,
  detection gap, corrective actions and owners.

## References

- `SECURITY.md` — reporting and supported versions.
- `docs/security/release-checklist.md` — release and signing steps.
- PHP security advisories: https://www.php.net/security/
- Packagist security: https://blog.packagist.com/
