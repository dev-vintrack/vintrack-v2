# PG-01 MariaDB Deployment Rehearsal Result

**Date:** 2026-08-14
**Production:** NOT ACCESSED / NOT MODIFIED
**Target MariaDB 10.6.27 runtime:** VERIFIED LOCALLY — PORTABLE WINDOWS x64
**Complementary runtime:** MySQL 8.4.3
**Final clone:** `vintrack_pg01_rehearsal_mariadb_20260814` (local/disposable; removed)
**PG-01 final status:** CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY — DEC-048

## Verdict Boundary

The MySQL evidence below is historical/complementary. The final MariaDB 10.6.27 portable rehearsal recorded in the last section supersedes the earlier runtime-unavailable status and supports closing PG-01 for schema compatibility only.

## Inputs

- source dump hash: `29a5aa66597a4d16fe6b0f91081718d5f34cf852782214782b691ffd78c50ccd`;
- imported baseline: 31 tables, 11 migrations, max batch 2;
- six-file reviewed SQL bundle under `docs/production/deployment/sql/`.

Bundle SHA-256:

- `01-PRECHECK.sql`: `2b885e2c94cf28676b5759bd4c38a01ebf57aedc8df5cebd0bcfbfb30cc4204c`;
- `02-SCHEMA-DELTA.sql`: `b5720144ed40eebbffff6e25e556360c07832075536a4c425a6fff5c4e1f15ab`;
- `03-INDEXES-CONSTRAINTS.sql`: `912e4e36c0494db9dc37d5696f4a39d799ef379df41ab350777a4a7d21b8737b`;
- `04-REFERENCE-CONFIG-DATA.sql`: `93e6bec2d9f05f307c03242dee41d4e83ca174b375d4d29e5bada2f1f659b8ee`;
- `05-MIGRATION-LEDGER-RECONCILIATION.sql`: `7e98e7e7ff9a26cd863f8543d71bb3bd8512cb70c7a22c8f5e9e0b4cf5b0fab2`;
- `06-POSTCHECK.sql`: `2581aa27a966b648136fba00f4dfdfa752233e82dd1112d9c993f2e68c1e2192`.

## Execution Record

Successful clean run: `2026-08-14T22:09:21Z`–`2026-08-14T22:09:28Z` (approximately 7.15 s).

| Step | Result | Evidence |
|---|---|---|
| Import production structure | PASS | 31 tables; 11 migrations. |
| `01-PRECHECK.sql` | PASS WITH EXPECTED TARGET OVERRIDE | All structural baseline checks passed; database identified as disposable clone; MariaDB-version flag correctly false on MySQL. |
| `02-SCHEMA-DELTA.sql` | PASS | 16 statements; 13 tables plus additive columns. |
| `03-INDEXES-CONSTRAINTS.sql` | PASS | Two ALTER statements. |
| `04-REFERENCE-CONFIG-DATA.sql` | PASS | 13 statements; no conflicts; empty structure-only data baseline. |
| `05-MIGRATION-LEDGER-RECONCILIATION.sql` | PASS | 46 statements; unique ledger names. |
| `06-POSTCHECK.sql` | PASS | No missing-object or duplicate-ledger rows. |

An earlier clean attempt stopped before ledger reconciliation with PDO error 2014 because the local harness had not drained SELECT result sets from file 04. Schema/index/reference statements had succeeded, but that clone was discarded and rebuilt. The successful run used the same SQL bundle and a corrected local harness; this was not a database/bundle error and no production retry occurred.

## Postcheck

- tables: 44/44;
- columns: 462/462;
- indexes: 147/147;
- FKs: 54/54;
- engines/collations: zero unexpected;
- missing target objects: zero;
- eight configuration columns/defaults: correct;
- null normalized values: zero (baseline had no business rows);
- duplicate case folios: zero;
- migration ledger: 55 unique entries; max batch 4.

## Second-run Safety

Read-only second run: `2026-08-14T22:10:30Z`.

- PRECHECK rejected the original baseline: 44 tables instead of 31, 13 target tables present, nine target columns present, ledger 55/batch 4.
- POSTCHECK remained fully green: 44 tables, 462 columns, 147 indexes, 54 FKs and no missing objects.
- Files 02–05 were not re-executed.

Model: **APPLY-ONCE BUNDLE WITH STRICT PRECHECK**. Universal idempotency is neither claimed nor desired.

## Application Compatibility

A temporary local Laravel connection was pointed read-only at the updated clone. Table existence and bounded count queries passed for:

- consultations/history projection base;
- notification cases;
- Evidence documents and malware scans;
- case events;
- notification outbox;
- portal notifications;
- consultation operations.

The history query using `provider_service_id`, `normalized_value`, criterion/value and ordering passed. All tables were empty because the authorized dump has no business rows. No provider, mail or external service was called. Full behavioral tests with representative MariaDB data remain outside this complementary schema check.

## MariaDB Status and PG-01 Recommendation

MariaDB 10.6.27 rehearsal: **BLOCKED / NOT EXECUTED — runtime unavailable locally**.

PG-01 recommendation: **OWNER ACTION REQUIRED / MARIADB TARGET REHEARSAL PENDING**.

Next minimum action, not executed: provide or authorize an isolated disposable MariaDB 10.6.27 runtime and repeat this exact import/bundle/second-run/application-schema sequence using the recorded hashes.

Cleanup: the disposable schema was explicitly verified (`before=1`), dropped locally after evidence capture, and verified absent (`after=0`). It is not recoverable; it contained only the authorized structure/ledger baseline and empty target tables.

## MariaDB 10.6.27 Target Rehearsal Authorization — Runtime Detection

On 2026-08-14 the Owner authorized a target-specific rehearsal, conditional on an already available isolated runtime. Read-only detection found:

- Docker CLI: not found;
- Docker Desktop executable/default installation paths: not found;
- Docker service: not found;
- Podman CLI/default installation path: not found;
- nerdctl: not found;
- WSL launcher: present, but Windows Subsystem for Linux is not installed and no distribution/runtime is available;
- Laragon remains MySQL 8.4.3 only.

Per the authorization's mandatory stop condition, no software/image was downloaded or installed, no service was started or modified, no container/database was created, and none of SQL files 01–06 was executed in this authorization.

Status: **MARIADB 10.6.27 RUNTIME NOT AVAILABLE — OWNER ACTION REQUIRED**.

PG-01 remains **OWNER ACTION REQUIRED / MARIADB TARGET REHEARSAL PENDING**. The prior MySQL 8.4.3 evidence remains complementary only.

## Final MariaDB 10.6.27 Portable Windows Rehearsal

### Official package and isolation

- official resolver: `https://downloads.mariadb.org/rest-api/mariadb/10.6.27/mariadb-10.6.27-winx64.zip`;
- official archive and checksum manifest: `https://archive.mariadb.org/mariadb-10.6.27/winx64-packages/`;
- filename: `mariadb-10.6.27-winx64.zip` (87,898,518 bytes);
- official/local SHA-256: `cc18bc6a0d42df6990ed91d1d7695e893b15168779272df43adf45dd75705a50` — MATCH;
- binary root: `C:\vintrack-tools\mariadb-10.6.27\mariadb-10.6.27-winx64`;
- temporary port: `65176` (loopback; not 3306);
- exact SQL version: `10.6.27-MariaDB`;
- no MSI, Windows Service, Docker, WSL, PATH, registry, Laragon or production change.

Final evidence run: `2026-08-14T16:38:55-06:00` to `2026-08-14T16:39:03-06:00`. The disposable datadir used ephemeral local credentials that were neither printed nor persisted in project documentation.

### Integrity and baseline

The approved source dump matched SHA-256 `29a5aa66597a4d16fe6b0f91081718d5f34cf852782214782b691ffd78c50ccd`. All six bundle hashes matched the values in the Inputs section before execution.

Baseline PASS: 31 tables, all InnoDB / `utf8mb4_unicode_ci`; 11 migrations/max batch 2; all five expected `provider_service_id` columns present; all 13 target tables and nine target columns absent; `rehearsal_database_ok=1`; `target_version_ok=1`. `production_database_ok=0` is expected for the disposable clone; the alternative rehearsal allowlist flag was 1.

### Exact bundle execution

| File | Result | Local duration | Warnings/errors |
|---|---:|---:|---|
| `01-PRECHECK.sql` | PASS | 36.021 ms | None. |
| `02-SCHEMA-DELTA.sql` | PASS | 273.648 ms | None. |
| `03-INDEXES-CONSTRAINTS.sql` | PASS | 68.728 ms | None. |
| `04-REFERENCE-CONFIG-DATA.sql` | PASS | 33.068 ms | None; menu conflicts 0/0. |
| `05-MIGRATION-LEDGER-RECONCILIATION.sql` | PASS | 104.662 ms | None; ledger 11/2 → 55/4. |
| `06-POSTCHECK.sql` | PASS | 47.100 ms | None. |

The six SQL files were not modified. The structure import took approximately 2,951.596 ms. Server stderr contained normal startup/shutdown notes only; no MariaDB warning or error.

### Target and application assertions

- 44 tables, 462 columns, 147 distinct indexes and 54 foreign keys;
- zero non-InnoDB or unexpected-collation tables and zero missing target objects;
- all 13 required tables, `consultations.normalized_value` and approved identity/history indexes present;
- ledger: 55 unique names, max batch 4, batch 3 = 34, batch 4 = 10;
- eight configuration defaults correct: 3/30/90 days, max pending 3, max files 8, 3 MiB, `America/Mexico_City`, reservation TTL 150 seconds;
- Laravel read-only connection PASS for consultations/history, cases, Evidence, malware scans, events, outbox, portal notifications and consultation operations;
- no provider, SMTP, production service or business-row operation occurred.

### Second run and apply-once contract

Only `01-PRECHECK.sql` and `06-POSTCHECK.sql` were executed again. PRECHECK rejected the updated baseline; POSTCHECK remained green. Files 02–05 were not repeated. Confirmed model: **APPLY-ONCE BUNDLE WITH STRICT PRECHECK**.

### MySQL 8.4.3 comparison

Both engines accepted the same reviewed SQL and produced identical target counts. MariaDB emitted no SQL warning and showed no material difference in DDL, indexes, FKs, nullability or timestamps/defaults. MariaDB displays the textual timezone default quoted through `INFORMATION_SCHEMA`; its semantic value matches.

- MariaDB SQL mode: `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION`;
- MySQL SQL mode: `ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`;
- portable MariaDB server defaults were `latin1/latin1_swedish_ci`, while the VINTrack database remained wholly `utf8mb4_unicode_ci`;
- MySQL server defaults were `utf8mb4/utf8mb4_0900_ai_ci`;
- earlier MySQL clean rehearsal: approximately 7.15 s end-to-end; final MariaDB import plus files 01–06: approximately 3.52 s. Empty-schema local timings are informational, not production performance evidence.

### Cleanup, limits and recommendation

MariaDB shut down normally, port `65176` stopped listening, and only the exact disposable datadir was removed. The verified ZIP, portable binaries and sanitized logs remain under `C:\vintrack-tools\mariadb-10.6.27`. Laragon MySQL subsequently returned `8.4.3` through the application connection.

This proves schema compatibility on real MariaDB 10.6.27, but Windows is not the Linux production host and the dump has no business rows. It does not prove production ALTER/backfill/index duration, metadata-lock impact, disk requirements or live-traffic behavior.

PG-01 final status: **CLOSED — VERIFIED FOR SCHEMA COMPATIBILITY** (Owner-approved through DEC-048 on 2026-08-14).

Production Authorization remains **NOT AUTHORIZED**. The next minimal action is Owner review and a separate decision on remaining Production Environment Verification Gates; it was not executed.

## Owner Formal Closure

On 2026-08-14 the Project Owner accepted the package/checksum, exact MariaDB runtime, baseline, unchanged SQL bundle, target counts, ledger, configuration defaults, Laravel read-only compatibility and strict second-run behavior. DEC-048 formally closes PG-01 for schema compatibility.

Historical migrations remain forward-only; recovery remains backup/restore or forward-fix. Real-row duration, metadata locks, normalized-value backfill duration, index-build duration, disk/temp-space, concurrent traffic and Linux/Neubox behavior are `DEPLOYMENT OPERATIONAL RISKS` and do not reopen PG-01.
