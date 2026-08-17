# VINTrack — Staging Deployment Manifest

**Scope:** Class B staging-code deployment only — `dev.vintrack.com.mx` / `public_html_dev`
**Source release:** `develop` at `07f0921440726c968c0460304d95bc9b46d600c4` (`07f0921`)
**Generated:** 2026-08-14
**Database action:** none during this code-release checkpoint. At its execution, `vintrack_dev` was at the confirmed 31-table baseline; the Owner later completed the separately authorized 01–06 bundle and reconciled it to the validated 44-table target. Do not repeat that bundle.

## Reproducible artifact

- Archive: `vintrack-staging-07f0921-20260814.zip` (held locally outside the repository and web root until controlled upload).
- SHA-256: `385ab579af0eb2ed70b3deed9affc66602ef7bef615bf4aeed241a69fe2eda2f`.
- Size: 67,389,846 bytes (64.27 MiB); 11,827 archive entries.
- Build method: Windows `tar.exe` ZIP archive from the selected release using the exclusion rules in this manifest. The archive is read/list verified before upload; it does not contain local `.env`, `storage/`, deployment SQL, database dumps, logs, sessions, generated Laravel caches, test material or Git metadata.

## Execution evidence — 2026-08-14

- The Owner uploaded and extracted the approved ZIP into the staging application tree, preserving the active `.env` and `storage/` tree. File Manager evidence shows `public_html_dev/.env` retained its prior timestamp and `storage/app/private` remains present outside `public_html_dev/public`.
- The temporary deployment ZIP was removed from hosting after extraction; no deployment archive remains in the public document root.
- The Owner provided a locally retained, readable backup archive before the switch: `backup-vintrack.com.mx-8-14-2026.tar.gz`, 215,249,732 bytes (205.28 MiB), SHA-256 `94f03fbecd667a13e8f3a8bbab548f1ce44500575f2ce5131f607a444893aa2c`, 14,733 entries. Its filename list contains `public_html_dev/.env` and `public_html_dev/storage/app/private`. It also contains legacy-tree content, so it is sensitive, must remain private, and must never be uploaded to a web-accessible path.
- Public read-only check: `https://dev.vintrack.com.mx/` rendered the VINTrack Laravel home page without visible boot failure or browser-console warnings/errors. Bootstrap was referenced from the approved self-hosted `/vendor/vintrack/` location. The browser client blocked direct inspection of standalone minified JS URLs; this is a local browser-control limitation, not evidence of a hosting asset error.
- No SQL, migration, Artisan command, Cron, permission change, `.env` change, storage probe, email, provider call or DB access was performed during this code-only checkpoint. At that time `vintrack_dev` remained the 31-table pre-migration baseline. The later separately authorized Owner bundle 01–06 completed the recorded 31→44 transition; it is not part of this checkpoint and must not be rerun.

## Release quality and dependency gate

- Local runtime: PHP 8.3.30.
- `composer.json` requires PHP `^8.2`; `composer.lock` and the installed non-development dependency tree passed `composer check-platform-reqs --no-dev` on PHP 8.3.30.
- Full Laravel suite: **116 passed, 545 assertions, 0 failures** (`php artisan test --compact`).
- `vendor/` is part of this artifact. The target has no SSH or Composer deployment workflow, so dependency installation cannot be deferred to hosting. It is the tree resolved from the authoritative `composer.lock`; no `composer update` is permitted.

## A. Include in the release archive

The archive is a complete application release, not a Git-diff patch. Include:

- `app/`, `bootstrap/` (excluding generated `bootstrap/cache/packages.php` and `bootstrap/cache/services.php`), `config/`, `database/` (excluding local SQLite, ad-hoc SQL and schema dumps), `public/`, `resources/`, `routes/`, `vendor/`;
- `artisan`, `composer.json`, `composer.lock`, `.gitattributes`, `.gitignore`, and any versioned runtime support file required by Laravel;
- the self-hosted frontend assets in `public/vendor/vintrack/`, including its `ASSET-MANIFEST.txt`.

Approved critical asset set present in the artifact: jQuery 3.7.1; Bootstrap 5.3.2; Bootstrap Icons 1.11.2; DataTables 1.13.6; Buttons 2.4.2; Responsive 2.5.0; JSZip 3.10.1; pdfmake 0.2.7; and the Spanish DataTables 1.13.6 resource. No migrated critical CDN origin was found in application views.

## B. Preserve on the staging server

The replacement must preserve, without download, display or modification:

- `public_html_dev/.env` — including `APP_KEY`, DB credentials, `DB_DATABASE=vintrack_dev`, mail/provider credentials and environment settings;
- `public_html_dev/storage/app/private` — existing evidence and all protected application files;
- staging runtime storage structure and data: `storage/app/`, `storage/framework/`, `storage/logs/`;
- existing server permissions. No recursive permission operation is allowed.

Generated Laravel cache files are not release inputs. The candidate keeps the `bootstrap/cache` directory structure only; no local generated config, route, package or service cache is deployed. No remote Artisan/cache operation is authorized.

## C. Exclude — never copy blindly

- `.env`, `.env.*` other than safe examples, local hosting notes, credentials and secrets;
- `.git/`, `.devin/`, editor/IDE metadata, `node_modules/`, `docs/`, `tests/`, local session notes and PHPUnit cache;
- all `storage/` contents, local logs, cache, sessions, compiled views, backups and evidence;
- `public/hot`, local `public/storage` link/target, local build caches;
- `deploy/`, database ad-hoc SQL, SQL bundles, database dumps, `*.sql`, `*.zip`, `*.tar.gz`, `*.bak`, local SQLite files and legacy deployment scripts;
- `vendor.zip`, package-manager frontend files and any file not required at runtime.

## Controlled server procedure

1. Create and verify a private archive of the complete current `public_html_dev` tree **outside** the document root and active application tree. It must include `.env` and `storage/app/private` without making either public.
2. Upload the release ZIP only to a private staging area outside `public_html_dev/public`; extract it there and verify the archive hash/contents.
3. Preserve `.env` and all required `storage/` data while switching the app tree. Retain the prior tree/backup until post-deployment verification completes. Do not replace these paths with local files.
4. If the File Manager cannot make that backup, retain protected paths, or safely perform a recoverable switch, stop before changing `public_html_dev` and request Owner action.
5. Verify `https://dev.vintrack.com.mx/` for HTTPS, normal Laravel boot, PHP 8.3.32 header if exposed, no autoload/syntax/dependency failure, and availability of local critical CSS/JS. Do not execute any write flow.

## Schema-bound limitations after code deployment

At the time of this code checkpoint, the release targeted the approved 44-table Laravel schema while staging still had 31 tables by Owner direction. A page that then failed only because it accessed an approved missing notification/idempotency/malware table was an **EXPECTED PRE-MIGRATION SCHEMA DEPENDENCY**, not evidence for manual schema repair or a failed artifact. The later authorized 01–06 bundle reconciled staging to the validated 44-table target. Current release preparation must use that target state and must not rerun the SQL bundle. A missing file/class/vendor asset, syntax error, autoload error or Laravel boot failure unrelated to the historical schema gap remains a deployment failure and triggers code rollback.

## Rollback readiness

The only rollback for this operation is restoration/switch-back to the verified private staging-code snapshot. `vintrack_dev` must not be restored, migrated or otherwise modified because it is outside this authorization.
