# VINTrack — Production Layout & Cutover Design

Date: 2026-08-14
Scope: design only
Production Readiness: **READY WITH CONDITIONS**
Production Authorization: **NOT AUTHORIZED**
Deployment: **NOT EXECUTED**

## 1. Executive Summary

VINTrack Laravel must replace the legacy site without exposing the Laravel application root. The recommended design is **Option B: a dedicated, stable production application root outside every HTTP Document Root**, physically separate from the current development installation. `vintrack.com.mx` will eventually point only to that production root's `public/` directory. `dev.vintrack.com.mx` will remain a separate staging/development environment with a non-production database, `.env`, storage, logs, cache and sessions.

Conceptual target paths use placeholders because the exact cPanel account home and final directory name must be confirmed by the Owner:

```text
<CPANEL_ACCOUNT_HOME>/
├── public_html/                    # legacy, retained temporarily for rollback
├── public_html_dev/                # separate dev/staging Laravel installation
│   └── public/                     # dev.vintrack.com.mx Document Root
└── vintrack_app/                    # recommended stable production root
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/                    # production-only persistent storage
    ├── vendor/
    ├── .env                        # production-only; never in artifact/manifest
    └── public/                     # future vintrack.com.mx Document Root
```

`/vintrack_app` is the Owner-approved cPanel-relative production-root name. Its account-home prefix must still be captured from cPanel before execution. No cutover action was executed.

## 2. Current Hosting Layout

Confirmed current state:

| Host | Role | Application root | Document Root | Runtime |
|---|---|---|---|---|
| `vintrack.com.mx` | Legacy PHP site, not released to clients | `/public_html` → `vintrack_system_db` (11 tables) | `/public_html` | PHP 8.3.32 effective |
| `dev.vintrack.com.mx` | New Laravel 12 staging application | `/public_html_dev` → `vintrack_dev` (31 tables) | `/public_html_dev/public` | PHP 8.3.32 effective; HTTPS 200 |

The legacy site will be replaced and is not a contractual blocker. It remains useful as a short-lived filesystem and Document Root rollback target.

## 3. Confirmed cPanel Capabilities

- SFTP and File Manager are available.
- cPanel Domains can edit a domain's Document Root; `dev.vintrack.com.mx → /public_html_dev/public` is observed evidence.
- Account Global PHP is 8.3; effective web PHP is 8.3.32.
- cPanel Cron exists, but CLI binary, working directory and actual execution remain unverified.
- SSH, Git Deployment and interactive PHP terminal are unavailable.
- Site Isolation is disabled by the hosting administrator.

The design therefore uses directory isolation, distinct configuration and a cPanel Document Root switch. It does not depend on SSH, Git, Composer, Node/NPM or interactive Artisan in production.

## 4. Security Requirements

1. Only Laravel `public/` may be web reachable.
2. `.env`, `vendor`, `storage`, `config`, `database`, `resources`, `routes`, tests, Composer metadata, source archives, SQL bundles and backups must remain outside the Document Root.
3. Production Evidence remains under production `storage/app/private`; it has no public symlink or predictable public URL and is downloadable only through authorized application logic.
4. Production and dev must not share `.env`, DB credentials/database, storage, logs, cache, sessions, generated files or Evidence.
5. Production uses `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, secure session settings and an approved non-fake malware provider configuration. Until a provider is approved, malware behavior must fail closed.
6. Self-hosted PG-18 assets and their manifest/hash must be included in the artifact; no critical CDN fallback is allowed.
7. Backup, SQL and artifact archives must never be stored under any active web root.

## 5. Option A — Reuse the Existing Laravel Installation

Future mapping: `vintrack.com.mx → /public_html_dev/public`, with dev either sharing that installation or being changed later.

Advantages:

- smallest initial file movement;
- quick Document Root switch;
- current Laravel rendering and PHP runtime already observed.

Disadvantages and risks:

- dev and production would share `.env`, database connection, storage, logs, cache, sessions and generated files;
- testing or a dev configuration change could affect production;
- production Evidence could be exposed to development workflows;
- rollback and troubleshooting cannot cleanly distinguish environment state;
- future artifact updates risk overwriting the only Laravel installation;
- leaving both domains attached to one installation creates ambiguous URLs/cookies/cache and accidental cross-environment writes.

Security: public isolation can be correct, but environment isolation is poor. This option is acceptable only as a short emergency bridge, never the steady state. If temporarily used, it should be read-only except for an explicitly approved cutover, use the production `.env` only after dev traffic is disabled/restricted, and last no longer than the single controlled cutover window plus immediate stabilization. Because the current authorization excludes implementation, it is not selected.

## 6. Option B — Neutral Stable Production Root

Create the distinct stable production application root `/vintrack_app` outside public web roots. `vintrack.com.mx` eventually points to `/vintrack_app/public`; dev remains at `/public_html_dev/public` with its own environment.

Advantages:

- strong dev/production separation;
- straightforward private storage and `.env` isolation;
- practical with SFTP/File Manager and cPanel Domains;
- stable production storage survives code artifact replacement when deployment procedures explicitly exclude `.env` and persistent storage;
- legacy `/public_html` can remain untouched for Document Root rollback;
- no dependency on symlink switching or SSH.

Tradeoffs:

- future in-place code updates require a maintenance window and strict manifest/order to avoid partial-upload exposure;
- the first production root must be fully prepared before the switch;
- rollback after schema/data changes still requires DB restore or forward-fix, not only a Document Root reversal;
- File Manager rename/move behavior and writable paths must be verified before execution.

This is the **recommended architecture under currently verified cPanel capabilities**.

## 7. Option C — Distinct Immutable Release Roots

Create release-specific roots such as `<RELEASES_ROOT>/<RELEASE_ID>/public`; point the domain at the approved release's `public/`, while dev remains separate.

Advantages:

- best artifact immutability and release traceability;
- fast code rollback by switching Document Root to the previous release;
- complete candidate release can be uploaded and hashed before activation;
- minimizes partial upload risk to the active release.

Constraints and unresolved risks:

- Evidence, logs, caches and `.env` must not be duplicated or lost per release;
- a safe shared-production storage/config mechanism normally needs verified symlink, bind, configurable base path or disciplined copy strategy;
- cPanel/File Manager atomic rename/symlink capability is not yet verified;
- switching code alone cannot roll back DB changes or reconcile Evidence created after cutover.

Option C is the preferred future evolution after a dedicated capability check proves safe shared storage/config and release switching. It is not the immediate recommendation because those prerequisites are currently assumptions.

## 8. Comparative Matrix

| Criterion | Option A: shared existing | Option B: stable production root | Option C: release roots |
|---|---|---|---|
| Public-root isolation | Good if `/public` only | **Good** | **Good** |
| Dev/production isolation | Poor | **Strong** | **Strong** |
| Private Evidence isolation | Poor/shared risk | **Strong, simple** | Strong only with verified shared-storage design |
| `.env` exposure/cross-use | High operational risk | **Low** | Low if shared config is designed safely |
| Initial complexity | Low | **Medium** | High |
| SFTP/File Manager practicality | High | **High** | Medium/unverified |
| Partial-upload protection | Low | Medium; maintenance required | **High** before activation |
| Code rollback | Medium | Medium | **High** |
| DB rollback | Restore/forward-fix | Restore/forward-fix | Restore/forward-fix |
| Preserve post-cutover Evidence | Risky sharing | **Clear persistent production storage** | Requires shared-storage proof |
| Rehearsal before switch | Limited | **Good** | **Excellent** |
| No SSH dependency | Yes | **Yes** | Only if cPanel capabilities suffice |
| Future maintenance | Poor | **Acceptable** | Best after prerequisites |
| Recommendation now | No | **Yes** | Later target |

## 9. Recommended Architecture

Select **Option B** now:

- one dedicated stable production application root outside `public_html` and outside `public_html_dev`;
- `vintrack.com.mx` Document Root set only to its `public/` subdirectory at cutover;
- `dev.vintrack.com.mx` retained as a separately configured staging environment;
- independent production `.env`, DB, storage, logs, cache and sessions;
- locally built complete artifact including `vendor/` and self-hosted assets;
- legacy retained intact for a bounded rollback period;
- DB rollback by consistent backup/restore or approved forward-fix.

## 10. Production Application Root

Recommended logical root: `<CPANEL_ACCOUNT_HOME>/vintrack_app` (shown in cPanel as `/vintrack_app`).

Selection rules:

- sibling of, not inside, `/public_html` and `/public_html_dev`;
- not reachable through another domain/subdomain;
- cPanel-relative name is Owner-approved as `/vintrack_app`; account-home prefix must be captured before execution;
- contains the complete Laravel application and production-only persistent `storage/`;
- contains no artifact ZIP, DB dump or `.env` backup after preparation;
- permissions follow least privilege; do not default to `777`.

The exact absolute path remains **OWNER ACTION REQUIRED** and must be captured from cPanel before deployment approval; this does not alter the approved `/vintrack_app` root name.

## 11. Production Document Root

Recommended future mapping:

```text
vintrack.com.mx → <CPANEL_ACCOUNT_HOME>/vintrack_app/public
```

No Laravel parent directory may be mapped as Document Root. `public/.htaccess`, `index.php`, compiled/self-hosted assets and public images belong in the web root; private Evidence, `.env`, source, logs and deployment files do not.

## 12. Dev/Staging Strategy

After go-live, `dev.vintrack.com.mx` should remain a **real, access-restricted staging environment**, physically rooted at `/public_html_dev` or a later separate staging root. It must use:

- a non-production database with synthetic/anonymized data only;
- a distinct `.env` and APP_KEY;
- `APP_URL=https://dev.vintrack.com.mx`;
- distinct storage, Evidence, logs, cache and sessions;
- no production SMTP recipients, paid provider calls or real malware provider unless separately authorized;
- access restriction appropriate to cPanel/application capability.

If that separation cannot be guaranteed at cutover, disable or restrict dev temporarily rather than leave it sharing production. It must not point indefinitely to the production installation.

## 13. Environment / `.env` Strategy

Maintain three separate concepts: local/build configuration, dev/staging `.env`, and production `.env`. The artifact must contain neither `.env` nor `.env` backups.

Production checklist, without values:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_URL=https://vintrack.com.mx`;
- unique production `APP_KEY` preserved across deployments;
- production-only `DB_*`;
- approved `MAIL_*` only after SMTP authorization;
- `FILESYSTEM_DISK=local` unless a later approved architecture changes it;
- secure cookie/session configuration for HTTPS and a production-specific cookie name/domain;
- `America/Mexico_City` application business timezone;
- provider credentials isolated from dev;
- fake malware scanner impossible in production; missing approved provider must fail closed.

Secrets are entered manually through an approved secure process, never committed, placed in manifests, screenshots or chat, and never copied from dev merely for convenience.

## 14. Database Separation and Promotion

- `vintrack_system_db` (11 tables) is legacy-only for `/public_html`; it is not a Laravel baseline, must never receive Laravel migrations/schema delta, and remains untouched for legacy rollback.
- `vintrack_dev` is the canonical 31-table Laravel staging baseline for `/public_html_dev`. It contains current staging data and is the only approved source for bundle 01–06.
- `vintrack_app` is the future Laravel production database for `/vintrack_app`; it is not created yet and must not be empty or derived from `vintrack_system_db`.
- Approved order: back up `vintrack_dev` under separate authorization; apply bundle 01–06 to `vintrack_dev` (31→44); validate the complete target through `dev.vintrack.com.mx`; then full-clone validated schema, data, indexes, constraints, ledger and approved reference/config data from `vintrack_dev` to `vintrack_app`.
- Current data in `vintrack_dev` is preserved; no cleanup, reset, CDC, replication, dual-write or incremental synchronization is designed because the Owner is the sole user during the controlled transition.
- Production Laravel connects only to `vintrack_app` after the validated clone. Dev continues using `vintrack_dev`; use distinct credentials.
- Use least-privilege accounts appropriate to each environment; do not reuse production credentials in dev.
- Before the staging migration, execute the approved `01-PRECHECK.sql` against `vintrack_dev`; only a passing strict baseline permits the separately authorized 02–05 bundle, followed by `06-POSTCHECK.sql`.
- The 01–06 bundle is apply-once, not universally idempotent. No SQL is executed by this design.
- Aggregate evidence, never credentials or customer data, enters deployment records.
- After cutover, verify that requests on each host write/read only its intended database before enabling business use.

## 15. Storage / Evidence Strategy

Production uses only `<PRODUCTION_APP_ROOT>/storage`, including `storage/app/private/notification-case-evidence`, logs, framework cache and temporary application state. Dev uses only `/public_html_dev/storage` (or its later staging equivalent).

Rules:

- never share Evidence storage between dev and production;
- no `public/storage` symlink is required for private Evidence;
- ordinary download remains authorized application streaming only;
- `storage/*` and `bootstrap/cache` must be writable by the effective web handler with least privilege;
- backup DB and production Evidence to the same consistency point;
- maintain manifest/hash for Evidence backup and preserve malware state/history metadata;
- rollback must never overwrite post-cutover Evidence silently;
- production storage persists across application artifact updates and is excluded from destructive replacement.

## 16. Artifact Deployment Strategy

Build locally from the approved commit on a PHP 8.3-compatible environment:

1. install production dependencies locally with `composer install --no-dev --prefer-dist --optimize-autoloader`;
2. run the approved full regression and platform requirements;
3. include `vendor/`, self-hosted PG-18 assets and `ASSET-MANIFEST.txt`;
4. exclude `.git`, `.env`, tests unless explicitly required for a diagnostic artifact, local caches, logs, DB dumps, backups and development-only files;
5. record commit, build timestamp, file list, sizes and SHA-256 manifest;
6. upload the complete candidate outside every active Document Root;
7. verify the uploaded manifest before domain switching;
8. production must not run Composer, Node or NPM.

For Option B updates, never blindly overwrite persistent `storage/` or `.env`. Because an active-directory atomic switch is not yet verified, future updates require maintenance mode/window, an ordered file plan and a verified previous artifact.

## 17. Cutover Sequence

This is a future sequence and requires separate authorization for every relevant Class B operation.

### Pre-cutover

1. Approve architecture, exact paths, responsible people, maintenance criteria, rollback authority and evidence checklist.
2. Under a separate authorization, migrate and validate `vintrack_dev` from 31 to 44 tables, preserving current data; only then full-clone it to `vintrack_app`.
3. Close or explicitly disposition remaining production gates: PG-05 provider, PG-06 storage/layout, PG-08–PG-17, PG-20 and artifact verification.
3. Confirm PHP 8.3 web runtime/extensions and independently verify CLI/Cron if required.
4. Build, test, manifest and approve the artifact locally.
5. Obtain consistent DB, production files/config and Evidence backups; record timestamp, size and hashes. Prove restore capability to the permitted extent.
6. Capture current `vintrack.com.mx → /public_html` mapping and legacy artifact/hash for rollback.
8. Prepare the distinct production root outside web roots; configure production `.env` without exposing secrets.
9. Verify writable `storage/*`, `bootstrap/cache`, upload temp behavior and private-path HTTP denial with a separately authorized disposable probe.
10. Estimate the maintenance window using a rehearsal against a representative clone and available disk/lock observations.

### Deployment

1. Start the approved maintenance window or otherwise prevent writes during the consistency-sensitive interval.
2. Reconfirm backups and rollback targets.
3. Upload/verify the complete production artifact outside the active web root.
4. Apply SQL 02–05 only through the separately approved DB mechanism after a green precheck; run 06 postcheck.
5. Verify production `.env`, DB isolation, storage, cache/log paths and fail-closed malware configuration.
6. Perform a pre-switch health check using an approved non-public mechanism; do not expose a generic diagnostic endpoint.
7. In cPanel Domains, change only `vintrack.com.mx` Document Root to `/vintrack_app/public`.
8. Capture the resulting mapping and timestamp.

### Post-cutover

1. Verify HTTPS status, certificate, expected Laravel page and no visible debug/error output.
2. Verify login/authorization with Owner-controlled accounts.
3. Verify DB connectivity and database identity without exposing credentials.
4. Verify CSS/JS/images, DataTables behavior, asset manifest and absence of critical CDN traffic.
5. Execute the approved synthetic Evidence flow: upload, quarantine/fail-closed scan state, authorized download only when CLEAN, IDOR denial and no direct HTTP access. Do not use real Evidence.
6. Verify portal notifications and, only if separately authorized, SMTP to an Owner-controlled mailbox.
7. Verify logs/cache and no stack traces or sensitive data.
8. Confirm Cron readiness; do not create Cron merely because the web cutover succeeded.
9. End maintenance only after all mandatory smoke checks pass and the Owner accepts the evidence.

## 18. Domain Switch Procedure

Future manual mechanism:

1. Sign in to cPanel.
2. Open **Domains**.
3. Locate `vintrack.com.mx` and choose **Manage**.
4. Record the current Document Root `/public_html` with timestamp/screenshot.
5. Enter the previously verified exact path to `/vintrack_app/public`.
6. Review that the value ends in `/public` and is not the application parent.
7. Save using cPanel's normal control.
8. Capture the success confirmation and resulting Domains table.
9. Run the post-switch smoke plan immediately.

Pre-switch evidence: target tree, artifact manifest/hash, `.env` checklist without values, PHP/runtime, storage probe, DB pre/postcheck readiness, backups and exact rollback root. Post-switch evidence: Domains mapping, HTTPS/status/headers, Laravel screen, assets/DataTables, auth/DB identity, private Evidence controls and logs.

Rollback path: repeat the same cPanel procedure and restore `vintrack.com.mx → /public_html` if a defined rollback trigger occurs and schema/data compatibility permits legacy operation.

## 19. Smoke Test Plan

| Area | Minimum evidence | Failure action |
|---|---|---|
| HTTPS/home | 2xx, valid TLS, expected Laravel page, no trace | rollback/hold maintenance |
| Authentication | Owner-controlled login/logout, secure cookie behavior | stop |
| DB identity | expected schema/version and safe aggregate read | stop |
| Authorization | client isolation, admin capability, IDOR denial | rollback |
| Assets | local JS/CSS/fonts/images; DataTables interactive; no critical CDN | stop or rollback |
| Evidence | synthetic allowed file, quarantine state, direct URL denial, authorized download rules | rollback/keep disabled |
| Malware | fake not selected; no provider means fail closed | do not enable Evidence workflow |
| Histories | client/admin page, filtering/pagination | assess severity |
| Notifications | Portal only initially; SMTP only with separate approval | isolate email channel |
| Logs/errors | writable log, no public debug/sensitive trace | rollback/hold |
| Cron | readiness evidence only; no implicit activation | keep disabled |

No smoke test may call a paid provider, debit a real wallet, use customer data or send mail to a real user.

## 20. Rollback Plan

### A. Document Root

Switch `vintrack.com.mx` from `/vintrack_app/public` back to `/public_html`, then verify HTTPS and the legacy page backed by unchanged `vintrack_system_db`. This is the fastest web rollback but does not reverse `vintrack_app` DB/storage effects.

### B. Database

Historical migrations are forward-only. Do not assume `migrate:rollback`. Choose one of:

- restore the consistent pre-cutover DB backup when data loss/reconciliation has been explicitly accepted and no post-cutover business writes must be preserved; or
- apply an approved forward-fix when restore would lose valid data or when schema is already consumed by the active artifact.

### C. Application files

Keep the verified production artifact and legacy tree unchanged during the stabilization period. Restore a prior manifest-verified artifact through SFTP/File Manager only under approval. Never restore `.env` from an unsecured archive.

### D. Evidence/storage

Document Root rollback must not delete or overwrite new production Evidence. If any Evidence is created after cutover, a complete point-in-time rollback may be impossible without reconciliation between DB metadata, scan history and physical files. Preserve the production storage read-only, capture a new backup/manifest, and reconcile before restore or forward-fix. This is a reason to keep the initial cutover in maintenance/read-only conditions until smoke checks finish.

Rollback triggers include sustained 5xx, failed authentication/DB identity, exposed `.env`/private files, broken Evidence authorization, missing required extensions, schema inconsistency or sensitive debug output.

## 21. Legacy Retention Plan

- Keep `/public_html` intact and non-modified through cutover and the Owner-approved stabilization/rollback period.
- After the domain switch, confirm no domain/subdomain/addon domain points to the legacy root except the documented rollback mapping.
- Do not place a public redirect or alternate hostname to the legacy unless explicitly approved.
- Archive only after production stability, backup/restore evidence and Owner approval.
- Any archive must be outside web roots, encrypted/controlled as appropriate, hashed and checked for secrets.
- Delete the legacy only under a later explicit authorization; no fixed deletion date is invented here.

## 22. Maintenance Window Considerations

A maintenance window is recommended because the cutover combines schema delta, possible `normalized_value` backfill/index creation, cache/config preparation, storage verification and a Document Root switch. Duration must be estimated from:

- rehearsal on a recent representative DB clone;
- production row counts and DB size;
- measured execution time of each SQL bundle stage;
- metadata-lock/index-build behavior and available temporary disk;
- SFTP/File Manager upload and hash-verification time;
- backup creation/download time;
- smoke and rollback rehearsal time;
- hosting resource limits and concurrent traffic.

No contractual duration is asserted. Abort thresholds and latest rollback decision time must be agreed before the window.

## 23. Security Controls

- Application root and all secrets/private data outside Document Root.
- Safe checks for `.env`, storage, vendor internals, logs, backups, SQL and archives must return denial/not found.
- `APP_DEBUG=false`; no stack traces, SQL, provider or SMTP details to users.
- HTTPS and secure session/cookie policy.
- Source-controlled self-hosted assets; target manifest/hash verification.
- No critical CDN fallback; CSP activated only after separate inventory/remediation approval.
- Evidence remains private, authorized and fail-closed for malware state.
- No fake malware provider in production.
- Backup and artifact files never under HTTP paths.
- Least privilege; no blanket `777`.
- Separate production/dev DB users, keys, sessions, cache, logs and storage.

## 24. Open Risks

1. PG-05 real malware provider remains unselected/unverified; Evidence cannot be treated as production-clean with the fake.
2. Final production root/path, ownership and web-handler writability are unverified.
3. `max_file_uploads`, `default_socket_timeout`, `upload_tmp_dir` and SAPI/handler remain unknown.
4. PHP CLI, Artisan, Cron working directory/frequency/output remain unverified.
5. SMTP, SPF/DKIM/DMARC, limits and bounce handling remain unverified.
6. Production `.env`, APP_URL/APP_DEBUG, logs/cache and header/CSP configuration remain unverified.
7. Real-data SQL duration, locks, temporary space and concurrent traffic remain deployment risks.
8. cPanel/File Manager atomic rename, symlink and immutable-release capabilities are unverified.
9. Full production backup/restore execution is not verified.
10. A DB rollback after post-cutover writes may lose data; Evidence requires explicit reconciliation.

## 25. Production Gates Impact

- **PG-06:** the design resolves the architectural decision for safe public/private separation, but does not verify the final path or writability. Status remains **PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED**.
- **PG-01:** remains CLOSED for schema compatibility; operational SQL risks remain deployment concerns.
- **PG-05:** unchanged — implemented locally, provider selection and production verification required.
- **PG-18:** remains CLOSED LOCALLY; artifact/hash and production CSP remain pending.
- **PG-07:** local backup/restore evidence remains valid; production backup/restore not execution-verified.
- **PG-08–PG-17 and PG-20:** unchanged; no external Gate is closed by design.
- **PG-19:** production remains deferred/not authorized.

## 26. Owner Decisions Required

Before any implementation/cutover authorization, the Owner must decide or approve:

1. the account-home absolute prefix for the Owner-approved `/vintrack_app` production root;
2. whether Option B is formally accepted and whether Option C receives a later capability study;
3. dev access restrictions and its permanent staging role;
4. maintenance window, abort criteria and responsible operators;
5. production DB credentials/account separation without disclosing values;
6. backup creation and restore authority;
7. Class B storage/writability/private-access probe;
8. malware provider subgate and whether Evidence functionality remains disabled until then;
9. PHP CLI/Cron, SMTP/DNS, APP_ENV/config and CSP verification authorizations;
10. legacy stabilization duration and later archive/deletion authorization.

## 27. Recommended Next Action

The minimum next action is a separately reviewed **Class B Staging Database Migration Rehearsal/Execution** against `vintrack_dev` only: create a verified staging backup; run bundle 01–06 in order with strict pre/postchecks; preserve all data; validate the 44-table target through `dev.vintrack.com.mx`; and stop before creating or cloning `vintrack_app`. Do not upload the production artifact, change the Document Root, configure production `.env` or begin cutover within that action.

## 28. Conclusion

Option B provides the safest architecture that is currently operable with verified Neubox/cPanel capabilities: a dedicated stable production installation, `public/` as the only HTTP root, and strict separation from dev. Option A creates unacceptable shared-environment risk. Option C offers better release atomicity but requires shared-storage and cPanel capabilities not yet verified.

Production Authorization remains **NOT AUTHORIZED**. Deployment remains **NOT EXECUTED**. No Document Root, remote file, `.env`, SQL, database, permission, storage, Cron, SMTP, DNS, provider or CSP change was performed.

PRODUCTION LAYOUT & CUTOVER DESIGN COMPLETE — READY FOR OWNER REVIEW

STOP.
