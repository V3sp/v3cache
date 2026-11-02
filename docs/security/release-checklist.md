# Security Release Checklist

Use this checklist for every release that changes dependencies, runtime support
or security-sensitive code.

## Before tagging

- [ ] `docker compose run --rm ci` is green (audit, PHPStan, PHPCS, PHPUnit).
- [ ] `docker compose run --rm ci composer validate --strict` passes.
- [ ] `docker compose run --rm ci composer audit --abandoned=fail` is clean.
- [ ] PHP matrix is green: `php83`, `php84`, `php85`.
- [ ] `CHANGELOG.md` has a dated entry with a `Security` section when relevant.
- [ ] `SECURITY.md` supported-versions table is current.
- [ ] SBOM generated and reviewed:
  ```bash
  docker compose run --rm ci composer cyclonedx:make-sbom \
    --output-format=JSON --output-file=sbom.json --omit=plugin \
    --output-reproducible --validate
  ```
- [ ] No secrets in the tree or in the release notes.
- [ ] Docker images and GitHub Actions are pinned to reviewed digests/SHAs.

## Tag and publish

- [ ] Create a signed tag:
  ```bash
  git tag -s vX.Y.Z -m "vX.Y.Z"
  git tag -v vX.Y.Z
  git push origin vX.Y.Z
  ```
- [ ] The `Release` workflow attests the source archive.
- [ ] Publish the tag on Packagist (or let the webhook pick it up).
- [ ] Verify a clean install on the minimum supported PHP:
  ```bash
  composer require v3sp/v3cache:^X.Y --dry-run
  ```
- [ ] Attach the SBOM and release notes to the GitHub release.

## Repository settings (manual, verify periodically)

- [ ] Branch protection on `main`/`master`: required review, required status
      checks, no force-push, no direct pushes for non-admins.
- [ ] 2FA required for maintainers; least-privilege GitHub/Packagist tokens.
- [ ] GitHub private vulnerability reporting enabled.
- [ ] Dependabot alerts and security updates enabled.
- [ ] `GITHUB_TOKEN` permissions default to read-only.
