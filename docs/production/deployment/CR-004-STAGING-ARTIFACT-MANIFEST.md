# CR-004 — Staging Artifact Manifest

**Classification:** STAGING DEPLOYED AND VALIDATED WITH OBSERVATIONS — NOT AUTHORIZED FOR PRODUCTION

**Prepared:** 2026-08-17

**Scope:** CR-004 artifact preparation, Owner-operated staging deployment and manual validation. No production, SQL, migration, Artisan, Cron, SMTP or new provider request was performed.

## Artifact identity

- Local archive: `C:\laragon\www\vintrack-v2\vintrack-staging-cr004-c23d662-20260817-windows.zip`
- Runtime payload source commit: `c23d662803c89f47477bd77803e2a90587746693` — `release: consolidate staging candidate and provider assessment`
- Archive SHA-256: `df55e505fd370f4ace987d4e165ea9937678ecc792ca2eae88006dda76676b72`
- Size: `66,835,751` bytes (63.74 MiB)
- ZIP entries: `10,291`
- Embedded `RELEASE-MANIFEST-SHA256.txt`: `10,286` payload file entries
- SHA-256 of embedded payload manifest: `a363e848baf265e442bc0e5b5bef1d86ca6d16a3f06587c19b8757837f5acf4b`

The payload is derived from committed runtime content. Documentation, tests, deployment material, `.env`, `storage/`, Git metadata and local caches are deliberately excluded from the archive.

## Archive compatibility correction

The earlier `vintrack-staging-cr004-c23d662-20260817.zip` has SHA-256 `4c7c348c60639922b4f1a193a061a935f32c817268431ad5c73e0c003ec7df8a`. It is bitwise intact and can be read/extracted by `tar`, but Windows File Explorer rejected it. It is superseded and **must not be used for upload**.

The replacement archive was generated through the standard Windows/.NET ZIP writer. Its entry index is readable with Windows `tar` and `System.IO.Compression`; required runtime files are present and prohibited root paths are absent. The Owner confirmed a complete local Windows File Explorer extraction, then uploaded the expected `66,835,751` bytes and extracted it in `dev.vintrack.com.mx` / `public_html_dev`, preserving `.env` and `storage/`.

## Included runtime payload

- `app/`, `config/`, `resources/`, `routes/`, `vendor/`
- `bootstrap/app.php`, `bootstrap/providers.php` and `bootstrap/cache/.gitignore`
- `database/migrations/`
- `public/css/`, `public/images/`, `public/vendor/`, `public/.htaccess`, `public/favicon.ico`, `public/index.php`, `public/robots.txt`
- `artisan`, `composer.json`, `composer.lock`, `.gitattributes`, `.gitignore`
- Per-file SHA-256 manifest embedded at the archive root

## Deliberately excluded

- `.env` and all environment secrets
- `storage/` including logs, sessions, framework cache and evidence
- `docs/`, `tests/`, `deploy/`, `.git/`, local SQLite databases and generated bootstrap cache
- Any backup archive or temporary build directory

## Local validation evidence

- `php artisan test --compact`: **130 tests, 605 assertions, 0 failures**
- `php vendor/bin/pint --test` on affected PHP files: **PASS**
- `composer check-platform-reqs --no-dev`: **PASS** with local PHP 8.3.30
- Archive presence/absence verification: **PASS**; required runtime paths present and forbidden root paths absent
- Standard ZIP index verification (`tar -tf`): **PASS**; 10,291 entries
- Windows File Explorer extraction: **PASS — OWNER CONFIRMED**
- Staging upload/extraction: **PASS — OWNER CONFIRMED**
- Staging smoke validation without provider calls: **PASS WITH OBS-CR004-STG-01 / OBS-CR004-STG-02**
- Archive hash independently recalculated after creation: **MATCH**

CR-004 introduces no migration or schema delta. The archive contains the approved local implementation for service-code-based assessment of `placas_service` and `nmvtis_plus`; it makes no provider call by itself.

## Post-staging state

1. CR-004 staging validation is accepted with the two documented observations; neither is corrected by this deployment.
2. This evidence does not close any production gate or authorize a production artifact upload, database clone, migration, SQL, Artisan, Cron, SMTP or provider request.
3. Any production action requires a separate Owner authorization and the applicable Production Environment Verification evidence.

Production Authorization remains **NOT AUTHORIZED**. Deployment remains **NOT EXECUTED**.
