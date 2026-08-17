# VINTrack — Production Environment Verification Gate Result

## Current execution addendum — Staging database 31 → 44 complete

Under the Owner's limited Class B authorization, the approved unchanged SQL bundle 01–06 was executed manually in phpMyAdmin against `vintrack_dev` only, after a verified private backup. PRECHECK passed the exact 31-table MariaDB 10.6.27 baseline. POSTCHECK confirms 44 tables, 462 columns, 147 indexes, 54 foreign keys, 55 ledger entries / batch 4, no missing target object, valid eight configuration columns, zero invalid configuration rows, zero null normalized values, zero duplicate case numbers and no duplicate migration. No production or legacy object was accessed or changed.

Next scope is non-mutating staging application validation only. This evidence does not establish Golden Staging, authorize `vintrack_app` creation/clone, or change Production Authorization.

## Current execution addendum — Golden Staging Candidate

The Owner completed the authorized non-mutating authenticated staging validation after the 44-table POSTCHECK: login/OTP/session/logout, expected Client/Admin menus, Client history/DataTables, notification-process/Evidence views without upload, Portal Notifications, Admin Global History and client filtering passed. No sensitive OTP or credential value is recorded. No provider call, upload, SMTP, Cron, malware integration, irreversible case action, production/legacy change, clone or additional SQL occurred.

Result: **GOLDEN STAGING CANDIDATE — READY FOR OWNER REVIEW**. This is not production deployment/authorization and does not close PG-06 or authorize `vintrack_app` creation/cloning.

## Current execution addendum — Class B Staging Code Deployment Prepared

The authorized staging-code-only release was prepared locally from `develop` commit `07f0921440726c968c0460304d95bc9b46d600c4` (`07f0921`). Local checks passed: PHP 8.3.30, Composer non-development platform requirements, and **116 tests / 545 assertions / 0 failures**. The generated release archive is `vintrack-staging-07f0921-20260814.zip`, SHA-256 `385ab579af0eb2ed70b3deed9affc66602ef7bef615bf4aeed241a69fe2eda2f`, 64.27 MiB, with 11,827 entries. It contains the complete locked vendor tree and approved local frontend assets while excluding `.env`, `storage`, logs, caches, sessions, SQL/dumps, tests and Git metadata.

Codex could not obtain an authenticated cPanel/File Manager session and did not request or handle credentials. The Owner then completed the approved File Manager operation and supplied the resulting evidence: the release ZIP was uploaded/extracted, `.env` and `storage/app/private` remain in the active tree, and the temporary ZIP was removed from hosting. The preceding backup is readable locally (205.28 MiB; SHA-256 `94f03fbecd667a13e8f3a8bbab548f1ce44500575f2ce5131f607a444893aa2c`) and contains the staging protected paths; because it also contains legacy-tree content it is sensitive and must stay private. A public read-only check rendered the expected Laravel staging home page without visible boot failure or console error. No DB query or mutation occurred during that code-only checkpoint; at that historical moment `vintrack_dev` remained at the 31-table pre-migration baseline. The Owner later executed the separately authorized bundle 01–06 and the 31→44 reconciliation recorded above; it must not be repeated. This does not alter production authorization.

**Phase:** PHASE 2 — CLASS B #1 ANALYZED
**Date:** 2026-08-14
**Scope:** local/read-only planning only
**Production access / Class B actions:** One authorized read-only SQL execution; incomplete evidence
**Production Readiness:** READY WITH CONDITIONS
**Production Authorization:** NOT AUTHORIZED
**Deployment:** NOT EXECUTED

## 1. Governance Preflight

Phase 1 was performed under the Owner authorization and the mandatory rector `docs/production/PRODUCTION-ENVIRONMENT-VERIFICATION-GATE.md`. `AGENTS.md` and every mandatory source were read. Confirmed: DEC-044 closes SPRINT-08; DEC-045 governs malware; DEC-046 governs frontend assets/CSP; DEC-047 closes the Local Production Hardening Gate; roadmap DEC-036; no current Sprint; production remains unauthorized and undeployed.

No Neubox/cPanel access, remote command, SQL, upload, mail, DNS, permission, `.env`, Cron, migration, provider or production action occurred.

## 2. Local Preflight

Inspected locally: Composer contract/lock, application/database/filesystem/mail/session/logging/timezone configuration, private Evidence storage and authorization, malware workflow, frontend assets, Artisan commands/scheduler, migrations, and deployment/rollback/readiness documents.

Findings:

- application timezone defaults to `America/Mexico_City`;
- Evidence uses the private `local` disk rooted at `storage/app/private`;
- `FakeMalwareScanner` exists only in tests and has no application/provider binding; it cannot prove or silently become production scanning;
- only `inventory:return-expired-credits` is declared in the local Scheduler, but production cannot rely on Scheduler until execution is verified;
- all 20 versioned frontend assets match the SHA-256 manifest;
- approved critical CDN origins are absent; only the unused Laravel `welcome` scaffold still references Bunny Fonts (OBS-LPH-03).

## 3. Current Production Gate Matrix

No Gate status changed by inference.

| Gate | Current status | Evidence position |
|---|---|---|
| PG-01 MariaDB | CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY | Exact MariaDB 10.6.27 portable rehearsal passed; real-data operational risks remain. |
| PG-02 idempotency | CLOSED | Closed locally. |
| PG-03 performance | CLOSED | Closed locally on representative data. |
| PG-04 migrations | CLOSED | Bootstrap/forward-only/restore/forward-fix accepted. |
| PG-05 malware | IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED | Not CLOSED. |
| PG-06 PHP runtime/extensions/private storage | PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED | Global and effective web PHP 8.3.32, required extensions, visible limits and basic HTTPS responses verified; final Laravel layout/private-storage writability and non-exposed runtime values remain unverified. |
| PG-07 backup/restore | CLOSED — VERIFIED LOCALLY | Not production-verified. |
| PG-08 PHP CLI | BLOCKED_EXTERNAL | Harmless target CLI evidence missing. |
| PG-09 Artisan/Cron | BLOCKED_EXTERNAL | Target bootstrap evidence missing. |
| PG-10 working directory | BLOCKED_EXTERNAL | cPanel path evidence missing. |
| PG-11 Cron operation | BLOCKED_EXTERNAL | Limits/runtime/overlap evidence missing. |
| PG-12 logs/cache | BLOCKED_EXTERNAL | Target config/writability evidence missing. |
| PG-13 APP_URL | BLOCKED_EXTERNAL | Target HTTPS/config evidence missing. |
| PG-14 SMTP | BLOCKED_EXTERNAL | Approved delivery evidence missing. |
| PG-15 SPF/DKIM/DMARC | BLOCKED_EXTERNAL | DNS/cPanel evidence missing. |
| PG-16 email limits | BLOCKED_EXTERNAL | Account limits missing. |
| PG-17 bounces | BLOCKED_EXTERNAL | Route/ownership evidence missing. |
| PG-18 frontend | CLOSED LOCALLY | Target artifact/header evidence remains. |
| PG-19 authorization | DEFERRED_BY_OWNER | Production NOT AUTHORIZED. |
| PG-20 production config | BLOCKED_EXTERNAL | Sanitized effective config missing. |
| PG-21 secrets/log sanitization | CLOSED LOCALLY | No production material inspected. |

## 4. BLOCKED_EXTERNAL Evidence Plan

| Gate | Minimum evidence | Least-invasive mechanism | Class | Risk |
|---|---|---|---|---|
| PG-01 | Version, variables, InnoDB/utf8mb4, columns/indexes/FKs, migration ledger. | Reviewed SQL pack via phpMyAdmin. | B | Low; schema/account disclosure. |
| PG-06 | Web PHP version/extensions/limits; private path and web-handler read/write/no-public-access proof. | cPanel screenshots first; one disposable probe only for gaps. | A then B | Medium; path/permission/test residue. |
| PG-08 | cPanel-supported PHP binary, `-v`, allowlisted modules, exit codes. | Fixed harmless CLI commands. | B | Low; remote execution/path disclosure. |
| PG-09 | Artisan bootstrap/version/list from correct directory. | One fixed harmless invocation. | B | Low/medium; bootstrap may log. |
| PG-10 | App/document-root mapping and Cron working directory. | File Manager/Cron screenshots, then command only if needed. | A then B | Low. |
| PG-11 | Minimum interval, timeout, overlap and measured bounded runtime. | Hosting UI first; temporary controlled execution last. | A then B | Medium; overlap/log/mail noise. |
| PG-12 | Drivers, writable paths, retention, safe log/cache/session behavior. | Sanitized UI/File Manager, bounded probe for gaps. | A then B | Medium; sensitive logs/cache mutation. |
| PG-13 | Sanitized APP_URL, certificate, redirect/canonical scheme/host. | Owner data plus public read-only HTTP. | A | Low. |
| PG-14 | Sanitized SMTP/TLS/sender plus one explicitly approved delivery. | UI first; single approved recipient last. | A then B | Medium/high; external communication. |
| PG-15 | SPF/DKIM/DMARC and cPanel deliverability status. | Public DNS and redacted UI. | A | Low. |
| PG-16 | Plan/account send rate, quota and size limits. | Provider/cPanel documentation. | A | Low. |
| PG-17 | Bounce route, retention, monitoring owner; optional controlled test. | Document policy, active test last. | A then B | Medium/high. |
| PG-20 | Allowlisted env/debug/URL/timezone/filesystem/log/session/cache/queue/mail values. | Owner-supplied redacted fields; never whole `.env`. | A; B only for runtime gap | High if secrets leak. |

## 5. Exact Manual cPanel Information Needed

Provide screenshots/transcriptions, redacting account names, email addresses, absolute account prefixes and all secrets unless essential:

1. cPanel version and hosting plan/limits.
2. Domain → Document Root mapping and proof it targets Laravel `public/`.
3. Effective domain PHP version and handler/SAPI if displayed.
4. Extensions: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`; also report `gd` and `zip` without assuming them mandatory.
5. INI values: `memory_limit`, `max_execution_time`, `max_input_time`, `post_max_size`, `upload_max_filesize`, `max_file_uploads`, `max_input_vars`, `default_socket_timeout`, `file_uploads`, and temporary-upload directory status.
6. phpMyAdmin engine/version and database collation; no credentials.
7. File Manager locations/permissions for app root, web root, `storage`, `storage/app/private`, `storage/framework`, `storage/logs`, `bootstrap/cache`; no Evidence names.
8. Proof private Evidence is outside direct HTTP routing and not publicly symlinked.
9. Cron UI minimum interval, supported PHP binary/example, timeout, overlap option, email behavior and working-directory rule.
10. Allowlisted config only: environment, debug boolean, URL, timezone, filesystem disk, log channel/level, session driver, cache store, queue connection, mail transport/host/port/encryption/sender domain.
11. SSL certificate/redirect and existing security-header interface/settings.
12. Email Deliverability SPF/DKIM, DMARC, mail quotas/rates/size and bounce owner.
13. Backup coverage, retention, timezone, format, restore size/process and whether support is required.

## 6. Exact Read-only SQL Proposed

`docs/production/verification/READ-ONLY-DB-VERIFICATION.sql` is the exact pack. It contains only `SELECT`, `SHOW` and `INFORMATION_SCHEMA` reads for version/variables, engine, charset/collation, schema/tables/columns/indexes/FKs and Laravel migration ledger. It extracts no business rows, privileges or credentials and has not been executed.

## 7. Diagnostic PHP Decision

No diagnostic PHP is required in the first sequence and none was created. cPanel UI, SQL metadata and fixed harmless CLI commands expose less surface.

If those cannot prove the effective web handler, a separate Class B proposal would expose only PHP version/SAPI, allowlisted extension booleans, selected non-secret INI limits, timezone and boolean writability for approved framework paths. It would never expose environment/server dumps, secrets, cookies/session IDs, full paths beyond need, arbitrary commands or full `phpinfo()`, and would require one-time access and verified removal.

## 8. PHP CLI Verification Plan

After cPanel identifies the supported binary (no path is invented):

1. `<approved-php-binary> -v`;
2. `<approved-php-binary> -m` (evaluate only allowlisted modules);
3. `<approved-php-binary> -r "echo PHP_SAPI, PHP_EOL, date_default_timezone_get(), PHP_EOL;"`;
4. from the verified app directory, `<approved-php-binary> artisan --version`;
5. optionally `artisan list --raw`, without invoking business commands.

Record timestamp, redacted exact command, exit code, duration and output hash. Every remote invocation is Class B and remains unexecuted.

## 9. Cron Verification Matrix

Use explicit bounded commands; do not depend on Laravel Scheduler.

| Command | Purpose | Proposed frequency | Overlap | Runtime/failure behavior |
|---|---|---|---|---|
| `inventory:return-expired-credits` | Return expired inventory. | Hourly. | Single-instance wrapper after capability review. | Measure target; non-zero exit, no rapid retry. |
| `notifications:auto-close --limit=100` | Close bounded eligible cases. | 10–15 min, conditional on measurement. | Lock wrapper + idempotent domain action. | Must finish before interval; retry next run. |
| `notifications:queue-deadline-reminders --limit=100` | Queue bounded reminders. | 10–15 min after outbox verification. | Lock + deduplication. | Sanitized failure; next bounded run. |
| `notifications:process-outbox --limit=100 --max-seconds=50` | Deliver bounded outbox. | 1–5 min only after SMTP authorization. | Single-instance + delivery idempotency. | 50-second cap plus startup; at-least-once. |
| `evidence:process-malware-scans --batch=25` | Process quarantine. | Not schedulable before real provider approval/integration. | Persistent claim/stale recovery + Cron lock. | Provider-dependent; fail closed/bounded retry. |

No Cron was created; frequencies are planning values, not SLAs or authorization.

## 10. Storage Verification Plan

Read-only File Manager evidence first. Confirm private path, ownership/permissions and absence of public route/symlink. Only after Class B approval, create one uniquely named synthetic file in an approved disposable subdirectory, read/hash/remove that file, prove web-handler (not merely File Manager/CLI) writability, and require HTTP denial. Never use real Evidence. Functional owner/admin/IDOR download checks require a separately approved synthetic-data plan.

## 11. HTTPS / Security-header Plan

For the Owner-confirmed hostname, observe HTTP→HTTPS, certificate chain/expiry, canonical host, HSTS, CSP/report-only, `X-Content-Type-Options`, referrer/framing and cache headers. Compare deployed critical asset hashes with the manifest. Do not activate CSP; first address inline Blade requirements and the unused Bunny Fonts scaffold. Local self-hosting alone does not prove target CSP.

## 12. SMTP / DNS Plan

First collect sanitized cPanel settings/limits, then query public SPF/DKIM/DMARC for the confirmed domain and define bounce ownership. Only under later Class B authorization send one application-generated message to one Owner-approved recipient, checking TLS/auth outcome, sanitized message identifier, outbox/delivery state, authentication results and—only if separately authorized—bounce behavior. No SMTP connection occurred.

## 13. Backup / Restore Plan

Inspect cPanel backup coverage/retention/timezone/download/restore workflow read-only. Before any future mutation, separately authorize a full backup/download and record timestamp, bytes, SHA-256 and a manifest covering DB, private Evidence, artifact and protected configuration. Validate archive readability offline. Do not test restore in production; production restore is prohibited absent incident-specific Owner authorization. Preserve the runbook distinction between restore and forward-fix; `migrate:rollback` is not a universal recovery mechanism.

## 14. Malware Provider Infrastructure Requirements

Required before provider selection: outbound HTTPS/DNS/TLS/CA support; cURL/OpenSSL versions; timeouts/firewall/proxy constraints; 3 MB body compatibility; API latency/rate/concurrency model; secure credential rotation; privacy/jurisdiction/retention/deletion/subprocessor contract; no public sample sharing; definition/result guarantees; explicit CLEAN/INFECTED/ERROR mapping with ambiguous outcomes fail-closed; retry/idempotency/audit/sanitized observability; adapter tests and authorized integration verification. No provider was selected/contacted; SHA-256 never creates trust.

## 15. Complete Class A/B/C Matrix

| Action | Class | Disposition |
|---|---|---|
| Local inspection, docs, manifest/hash review, SQL-pack preparation | A | Completed. |
| Owner-provided redacted cPanel evidence; confirmed-host public DNS/HTTPS observation | A | Requested/planned, not remotely accessed. |
| Open/authenticate to cPanel/File Manager | B | Not authorized/executed. |
| Execute read-only SQL | B | Not authorized/executed. |
| Execute remote PHP/Artisan | B | Not authorized/executed. |
| Upload/run/remove diagnostic; storage probe | B | Not required now/not executed. |
| Create/trigger Cron; edit runtime config/permissions/cache; backup/download; SMTP test | B | Not authorized/executed. |
| Deploy/replace artifact or run production migrations | C under current authority | Prohibited. |
| Change business data, debit, use fakes/paid providers/real scanner, bulk mail | C | Prohibited. |
| Restore/delete/destructive DB actions, DNS changes, new features/SPRINT-09 | C | Prohibited. |

## 16. Ordered Verification Sequence

1. Receive and reconcile the sanitized manual cPanel evidence in section 5.
2. Confirm hostname/document root/PHP/runtime/hosting constraints without Codex production login.
3. Observe public HTTPS/security headers/DNS only for the confirmed target.
4. Request Class B for the reviewed SQL pack; evaluate PG-01/schema evidence.
5. If needed, request fixed harmless PHP CLI, then one Artisan bootstrap command.
6. Resolve storage/log/cache through UI; use one disposable probe only for gaps.
7. Measure bounded commands manually before proposing any Cron; malware/outbox wait for their dependencies.
8. Verify backup creation/download and offline readability before future mutations; never test production restore destructively.
9. Select/approve malware provider separately, implement locally, then request integration verification.
10. Request one SMTP test only after configuration, DNS, limits, bounce ownership and command safety.
11. Recalculate Gates only from reproducible evidence; production authorization/deployment remain separate decisions.

## 17. First Recommended Class B Action

Request authorization to execute `docs/production/verification/READ-ONLY-DB-VERIFICATION.sql` once through phpMyAdmin against the explicitly selected VINTrack production schema, using read/metadata permissions, and return redacted output, timestamp and schema identifier. Preconditions: manual schema confirmation, final SQL checksum, start/end timestamps and agreed evidence retention/redaction.

This action was not executed and is not authorized by Phase 1.

## 18. Conclusion

Phase 1 produced the least-invasive evidence plan and read-only SQL pack. No Gate was closed or reinterpreted and no target verification action began.

## 19. Phase 2 — Class B #1 Database Verification

The Owner confirmed `vintrack_dev` visually in phpMyAdmin. The approved SQL artifact (`SHA-256 fed9690bddf7069f77eab3d0c5dbb637f64426ddcfac5fd03a6e72b818e585dd`) was submitted exactly once from `2026-08-14T20:20:47.101Z` to `2026-08-14T20:20:48.708Z` UTC.

The final migration-ledger statement failed because phpMyAdmin resolved the unqualified `migrations` reference under `information_schema`. phpMyAdmin did not retain reproducible earlier result sets in the terminal error view. Exact version/configuration and migration count/latest batch therefore remain `NOT DETERMINED`; no query was repeated or modified.

The confirmed structure view showed 31 Laravel staging tables versus 44 expected by the current migration model. Thirteen notification/idempotency/malware tables are absent, constituting material schema drift. PG-01 recommendation at that historical point: `OWNER ACTION REQUIRED`; the later MariaDB rehearsal and DEC-048 closed schema compatibility. Full redacted evidence and the exact missing-table list are recorded in `docs/production/verification/CLASS-B-01-DB-VERIFICATION-EVIDENCE.md`.

The next minimum action is a separately reviewed and authorized compact supplemental read-only SQL pack. It was not executed.

## 20. Owner-supplied `vintrack_dev` Structure Dump

The Owner supplied a phpMyAdmin structure dump whose only data rows are the `migrations` ledger. It was analyzed locally as text and was not imported or executed.

Confirmed:

- `vintrack_dev` on `10.6.27-MariaDB-cll-lve-log`;
- 31 Laravel staging tables, all InnoDB / `utf8mb4_unicode_ci`;
- 11 migration ledger entries, latest batch 2;
- production ledger ends at `2026_07_08_000000_add_flags_json_to_consultations_table`;
- 13 expected tables are absent;
- `consultations.normalized_value`, its two approved indexes and eight notification-case configuration columns are absent;
- several later/manual structures exist despite absent migration-ledger entries.

The dump header's PHP 8.4.24 value belongs to phpMyAdmin's environment and is not evidence of the VINTrack application PHP handler.

The version/engine portion of PG-01 is now verified. PG-01 as a whole remains `OWNER ACTION REQUIRED` because material schema drift and migration-ledger divergence prevent safe closure. The next minimum action is an offline migration rehearsal against a disposable MariaDB 10.6.27 clone of this exact structure; it was not executed and production remains unchanged.

## 21. PG-01 Local Reconciliation and Complementary Rehearsal

The 55-migration reconciliation and six-file reviewed SQL bundle are complete. Final classification: 11 `APPLIED_REGISTERED`, 34 `APPLIED_UNREGISTERED`, 10 `NOT_APPLIED_REQUIRED`, and zero in the remaining permitted classifications. The 12-table partial-bundle discrepancy was an omission of `notification_case_document_scans`; Owner authorization corrected the canonical delta to 13 tables, 31→44.

A clean disposable MySQL 8.4.3 rehearsal passed import, precheck, schema delta, indexes/constraints, reference/config data, ledger reconciliation and postcheck. Final state: 44 tables, 462 columns, 147 indexes, 54 FKs, 55 ledger entries/max batch 4, no missing objects. A read-only second run rejected the old baseline and retained a green postcheck. Local Laravel read-only schema queries passed for histories, cases, Evidence/scans, events, outbox, portal notifications and consultation operations.

MariaDB 10.6.27 remains unavailable locally, so this is complementary evidence only. PG-01 is **OWNER ACTION REQUIRED / MARIADB TARGET REHEARSAL PENDING**, not CLOSED. Production was not accessed or modified.

### MariaDB target-runtime detection

A subsequent Owner authorization permitted the exact MariaDB 10.6.27 rehearsal only if an appropriate isolated runtime already existed. Read-only detection found no Docker/Docker Desktop, Podman, nerdctl or installed WSL runtime. The mandatory stop condition applied. No installation, image pull, container, database or SQL execution occurred. Status remains `MARIADB 10.6.27 RUNTIME NOT AVAILABLE — OWNER ACTION REQUIRED`; PG-01 is not CLOSED.

## 22. PG-01 MariaDB 10.6.27 Portable Rehearsal — Final Evidence

The Owner subsequently authorized the official MariaDB 10.6.27 Windows x64 portable ZIP. Official and local package SHA-256 matched (`cc18bc6a0d42df6990ed91d1d7695e893b15168779272df43adf45dd75705a50`). The isolated final run used loopback port `65176`, ephemeral credentials and the allowlisted disposable database `vintrack_pg01_rehearsal_mariadb_20260814`; it did not install a service, alter PATH/Laragon or access production.

The approved `vintrack_dev` staging-structure dump reproduced the 31-table, 11-migration/max-batch-2 baseline. All six unchanged deployment SQL files passed in order on exact `10.6.27-MariaDB`. Final state: 44 tables, 462 columns, 147 indexes, 54 FKs, 55 unique migrations/max batch 4 (batch 3 = 34; batch 4 = 10), all 13 required tables, normalized consultation identity/indexes and all eight configuration defaults. No SQL warning/error or material MariaDB schema defect occurred.

Laravel read-only schema compatibility passed. A second run executed only PRECHECK/POSTCHECK: PRECHECK rejected the already-updated baseline and POSTCHECK passed; files 02–05 were not repeated. MariaDB stopped normally, the exact disposable datadir was removed and Laragon MySQL remained operational at version 8.4.3.

PG-01 is **CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY**. This does not verify Linux-host behavior, production-data duration, metadata locks, backfill/index-build duration, disk capacity or live traffic. Production Readiness remains **READY WITH CONDITIONS**; Production Authorization remains **NOT AUTHORIZED**; deployment was not executed.

Detailed evidence: `docs/production/verification/PG01-MARIADB-DEPLOYMENT-REHEARSAL-RESULT.md`.

## 23. PG-01 Owner Formal Closure

DEC-048 records the Project Owner decision dated 2026-08-14: `PG-01 — CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY`. The accepted evidence and operational limitations remain exactly as documented above. Real-data duration, metadata locks, backfill/index-build duration, disk/temp-space, concurrent traffic and Linux/Neubox behavior are deployment operational risks and do not reopen PG-01.

Production Readiness remains **READY WITH CONDITIONS**. Production Authorization remains **NOT AUTHORIZED**. Deployment remains **NOT EXECUTED**. The next verification action requires separate Owner authorization.

## 24. PG-06 Manual / Read-only Evidence Pass

**Evidence date:** 2026-08-14
**Method:** Owner-supplied cPanel/File Manager/Chrome DevTools screenshots and consolidated redacted transcription
**Remote action by Codex:** NONE
**Configuration change:** NONE

### Web PHP runtime

- cPanel PHP Selector account default: PHP 8.2;
- `vintrack.com.mx` and `dev.vintrack.com.mx`: PHP 8.2 account default;
- effective production response: `X-Powered-By: PHP/8.2.32`;
- frontend server header: nginx;
- PHP SAPI/handler: NOT ESTABLISHED.

Result: **FAIL**. VINTrack's approved production baseline is PHP 8.3. A selected and effective PHP 8.2.32 runtime does not satisfy it. The response also exposes the exact PHP version, recorded as a hardening observation.

### Extensions

Visually enabled in PHP Selector: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`; MySQL support also showed `mysqlnd`/`nd_pdo_mysql`. Complementary `gd` and `zip` were enabled.

Result: **PASS BY MANUAL UI EVIDENCE** for the selected PHP 8.2 family. This does not override the PHP-version failure and must be reconfirmed after switching to the approved PHP 8.3 family.

### PHP limits and uploads

| Setting | Evidence |
|---|---:|
| `memory_limit` | 512M |
| `max_execution_time` | 90 s |
| `max_input_time` | 60 s |
| `max_input_vars` | 5000 |
| `upload_max_filesize` | 128M |
| `post_max_size` | 128M |
| `file_uploads` | ON |
| `log_errors` | ON |
| `display_errors` | OFF |
| `max_file_uploads` | NOT VERIFIED |
| `default_socket_timeout` | NOT VERIFIED |
| `upload_tmp_dir` / fallback writability | NOT VERIFIED |

Visible limits provide ample nominal headroom over the VINTrack maximum of 3 MiB per file and eight active files (24 MiB before multipart overhead). End-to-end multipart acceptance and web-server limits remain unverified.

### Document root and private storage

- production domain Document Root: `/public_html`;
- development domain Document Root: `/public_html_dev/public`;
- development Laravel application root is separate from its `public/` directory;
- development `storage/app/private`, `storage/framework`, `storage/logs` and `bootstrap/cache` exist;
- visible development directories were generally `0755`, development `public/` was `0750`, and regular files were generally `0644`.

Development private evidence classification: **SUPPORTED BY PATH DESIGN**. `storage/app/private` is outside the development web root and the local VINTrack filesystem configuration uses that private path.

Production Laravel layout: **UNVERIFIED**. The current production root contains legacy/current content directly under `/public_html`; the evidence does not establish a final Laravel app-root/`public/` separation. Visible permissions prove metadata only, not web-handler writability. No Evidence filename or content was inspected.

### Capacity

- quota: approximately 48 GiB;
- used: approximately 313–315 MiB (cPanel views differ slightly by accounting/rounding);
- approximate available capacity: 47.7 GiB;
- file usage: 14,793 / 350,000 (4.23%);
- independently labelled inode metric: NOT AVAILABLE.

Result: **PASS FOR INITIAL TECHNICAL HEADROOM**, not proof of indefinite sufficiency. At the theoretical maximum of 24 MiB per case, retention and growth still require an approved capacity model.

### HTTPS and security headers

- AutoSSL domain validation and automatic renewal shown for production/development domains;
- production certificate: Let's Encrypt, RSA 2048, production and `www` covered, expiration shown as 2026-11-09;
- effective browser navigation reached HTTPS even though cPanel `Force HTTPS Redirect` displayed OFF; the enforcement layer is NOT DETERMINED;
- observed headers: HSTS one year, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, Referrer Policy, and CSP limited to `upgrade-insecure-requests`;
- `.htaccess` management capability exists through File Manager;
- final restrictive CSP, `Permissions-Policy` and the exact header-management layer remain unverified/not configured by this pass.

Result: TLS/domain coverage and effective HTTPS **PASS**. Existing CSP is not the target application CSP contemplated by PG-18.

### PG-06 classification and gaps

**PG-06 — PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED**.

Material reasons:

1. final Laravel production Document Root/private-storage separation is not established;
2. runtime writability of `storage/*`, `bootstrap/cache` and upload temporary storage is not established;
3. `max_file_uploads`, `default_socket_timeout`, `upload_tmp_dir`, SAPI/handler and end-to-end multipart behavior remain unknown.

The PHP-version failure was remediated by the separately authorized Class B action documented below. The least invasive remaining action is to establish the final Laravel production layout and then request a separately authorized disposable storage/upload probe.

Only after PHP 8.3 and the final Laravel production layout exist should the Owner consider a separate Class B disposable storage/upload probe. That future probe would write/read/hash/delete one synthetic file under the intended private directory and verify HTTP denial; it is mutating, remains unauthorized and was not executed.

Production Readiness remains **READY WITH CONDITIONS**. Production Authorization remains **NOT AUTHORIZED**. Deployment remains **NOT EXECUTED**.

PG-06 MANUAL EVIDENCE REVIEW COMPLETE — READY FOR OWNER REVIEW

STOP.

## 25. PG-06 Class B — Global PHP 8.3 Remediation

On 2026-08-14 the Owner authorized the single cPanel change `Account Global PHP 8.2 → 8.3`. Prechange evidence reconfirmed PHP 8.2 globally and both `vintrack.com.mx` and `dev.vintrack.com.mx` inheriting the account default. Site Isolation remained disabled. No extension, PHP option, file, permission, Document Root or application configuration was changed.

Postchange evidence:

- Account Global Settings: **PHP 8.3 (current)**;
- both domains continue without a per-domain override and their effective HTTPS responses report **PHP/8.3.32**, corroborating inheritance of the new account default;
- required extensions reconfirmed: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `gd` and `zip`;
- options reconfirmed: `memory_limit=512M`, `max_execution_time=90`, `max_input_time=60`, `max_input_vars=5000`, `post_max_size=128M`, `upload_max_filesize=128M`, `file_uploads=ON`, `display_errors=OFF`, `log_errors=ON`;
- `max_file_uploads`, `default_socket_timeout` and `upload_tmp_dir`: **NOT VERIFIED** because PHP Selector does not expose them;
- `dev.vintrack.com.mx`: HTTPS `200 OK`, expected Laravel VINTrack home rendered, no blank page, 500, visible stack trace/warning/deprecation or browser console error;
- `vintrack.com.mx`: HTTPS `200 OK`, expected legacy construction page rendered;
- effective header on both domains: `X-Powered-By: PHP/8.3.32`;
- observed security headers remained present; none was changed;
- accepted legacy impact: **NONE OBSERVED**;
- rollback: **NOT EXECUTED**, because the prioritized Laravel application remained healthy and all required extensions were present.

PG-06 is not CLOSED. The PHP runtime, required extensions, listed limits and basic HTTPS responses are now verified. Remaining gaps are the final production Laravel public/private layout, web-handler writability for `storage/*` and `bootstrap/cache`, upload temporary-path behavior, private evidence HTTP denial, multipart behavior, SAPI/handler, `max_file_uploads`, `default_socket_timeout` and `upload_tmp_dir`.

Recommendation: retain PHP 8.3.32 and, only after the final layout exists and the Owner separately authorizes it, execute the minimum disposable storage/upload probe. No such next action was executed.

Production Readiness remains **READY WITH CONDITIONS**. Production Authorization remains **NOT AUTHORIZED**. Deployment remains **NOT EXECUTED**.

PG-06 PHP 8.3 REMEDIATION COMPLETE — READY FOR OWNER REVIEW

STOP.

## 26. DEC-049 Database Promotion Strategy Correction

DEC-049 corrects the database-role interpretation without changing historical evidence: `vintrack_system_db` is the untouched 11-table legacy PHP database for `/public_html`; it is not the Laravel baseline and never receives bundle 01–06. `vintrack_dev` is the 31-table Laravel staging baseline for `dev.vintrack.com.mx`. The approved future promotion is `vintrack_dev` 31→44 through the bundle, complete staging validation, then a full clone to the not-yet-created `vintrack_app` for `/vintrack_app`.

No SQL, backup, clone, database creation, hosting or Document Root change was executed by this correction. PG-01 remains **CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY** and PG-06 remains **PARTIALLY VERIFIED — FINAL LAYOUT/STORAGE VERIFICATION REQUIRED**.
