# VINTrack — Staging Database Migration Execution Checklist

**Authorization:** Class B — `vintrack_dev` 31 → 44 only
**Status:** SQL BUNDLE COMPLETE — STAGING APPLICATION VALIDATION PENDING
**Prohibited targets:** `vintrack_system_db`, `vintrack_app`, `/public_html`, and production are out of scope.

## Verified local preflight

- Local regression: **116 tests passed, 545 assertions, 0 failures**.
- Bundle provenance: unchanged files previously passed against MySQL 8.4.3 and MariaDB 10.6.27.
- Do not use `php artisan migrate`; execute only these exact files, one at a time and in this order:

| Order | File | SHA-256 |
|---:|---|---|
| 01 | `01-PRECHECK.sql` | `2b885e2c94cf28676b5759bd4c38a01ebf57aedc8df5cebd0bcfbfb30cc4204c` |
| 02 | `02-SCHEMA-DELTA.sql` | `b5720144ed40eebbffff6e25e556360c07832075536a4c425a6fff5c4e1f15ab` |
| 03 | `03-INDEXES-CONSTRAINTS.sql` | `912e4e36c0494db9dc37d5696f4a39d799ef379df41ab350777a4a7d21b8737b` |
| 04 | `04-REFERENCE-CONFIG-DATA.sql` | `93e6bec2d9f05f307c03242dee41d4e83ca174b375d4d29e5bada2f1f659b8ee` |
| 05 | `05-MIGRATION-LEDGER-RECONCILIATION.sql` | `7e98e7e7ff9a26cd863f8543d71bb3bd8512cb70c7a22c8f5e9e0b4cf5b0fab2` |
| 06 | `06-POSTCHECK.sql` | `2581aa27a966b648136fba00f4dfdfa752233e82dd1112d9c993f2e68c1e2192` |

Source directory: `docs/production/deployment/sql/`.

## Owner action A — mandatory backup before SQL

1. In phpMyAdmin, visually select **only** `vintrack_dev` and verify the selected database name before proceeding.
2. Use **Export** → **Custom** → SQL; select all tables and choose **Save output to a file** with gzip compression. Include structure and data, `CREATE TABLE`, indexes, foreign keys and the `migrations` ledger. Do not export or select any other database.
3. Save the downloaded archive outside every web root. Record local timestamp, filename, byte size and SHA-256; retain it privately.
4. Confirm the archive is readable locally (listing/decompression only is enough). Do not restore it.
5. If export, download, hash or basic readability verification fails, **STOP**. Do not open the SQL tab or execute the bundle.

## Owner action B — PRECHECK only

1. Return to phpMyAdmin and select **`vintrack_dev`** again. Do not rely on a previous tab or remembered selection.
2. Open the **SQL** tab. Open the local `01-PRECHECK.sql` from the source directory above in a text editor and copy its complete content into the SQL window. Confirm the window is not empty before clicking Execute.
3. Execute only that file. Record start/end time and capture/export the complete result grid.
4. Continue only if every `*_ok` result is `1`, table count is `31`, all tables are InnoDB / `utf8mb4_unicode_ci`, migrations are `11` with max batch `2`, target tables/columns are absent, and the selected database is `vintrack_dev`.
5. Send the result evidence to Codex. **Do not execute 02–05 yet.**

## Conditional mutation sequence — only after PRECHECK review

For each file 02 through 05: select `vintrack_dev` anew, paste/upload only the named file, record start/end time and complete result, then stop immediately on any warning/error/timeout/connection loss/duplicate/FK/index/ledger conflict. Do not continue to the next file after any abnormal result.

After successful 02–05, execute 06 separately. Required target: **44 tables, 462 columns, 147 indexes, 54 foreign keys, 55 ledger entries, max batch 4, zero missing target objects**.

No restore, forward-fix, Laravel Artisan, manual schema repair, provider call, SMTP, Cron, upload probe or production action is permitted by this checklist.

## Execution evidence — backup and PRECHECK

**Backup:** Owner supplied `vintrack_dev (1).sql.gz` from a private local Downloads path. It is readable as gzip, 27,832 bytes (197,418 uncompressed characters), SHA-256 `0866c11813e4e125be64440de10fbd8b36b2a73eac5f83c77da2dcf1daea9f94`. Non-sensitive structural checks confirmed `CREATE TABLE`, `INSERT`, `migrations`, `consultations` and `users` are present. No backup content was imported, printed or otherwise executed. Owner did not supply an execution timestamp, so it is recorded as **NOT DETERMINED**.

**`01-PRECHECK.sql`: PASS.** Owner-supplied phpMyAdmin results confirmed selected database `vintrack_dev`; `production_database_ok=1`; exact `10.6.27-MariaDB-cll-lve-log` with target-version flag `1`; 31 tables with `baseline_table_count_ok=1`; zero non-InnoDB and unexpected-collation tables; all 31 required baseline tables; five required `provider_service_id` columns; zero target tables/columns; and migration ledger `11` / max batch `2`. The listed ledger entries match the approved baseline. Execution timestamp is **NOT DETERMINED** because it was not supplied.

**`02-SCHEMA-DELTA.sql`: PASS BY OWNER-SUPPLIED phpMyAdmin EVIDENCE.** All displayed statements returned the phpMyAdmin green success result with no SQL error, timeout, duplicate-object or FK failure visible. Evidence covers the additive target tables, `global_configuration` notification-case columns, `consultations.normalized_value`, Evidence malware fields and `notification_case_document_scans`. The approved 277-line SQL file contains no `DROP`, `TRUNCATE`, data `DELETE`, data `UPDATE` or data `INSERT` statement; its only `ON DELETE`/`ON UPDATE` occurrences are restrictive foreign-key actions. It is therefore structurally additive and does not contain a bundle backfill. Owner did not supply execution start/end timestamps, so duration is **NOT DETERMINED**.

**`03-INDEXES-CONSTRAINTS.sql`: PASS BY OWNER-SUPPLIED phpMyAdmin EVIDENCE.** Both approved index `ALTER TABLE ... ADD KEY` statements returned the phpMyAdmin green success result: the consultation normalized-identity indexes completed in approximately `0.0641` seconds and the Evidence malware-queue index in approximately `0.0219` seconds. No SQL error, timeout, duplicate-index or constraint failure is visible. The exact file has no destructive DDL/DML; its only matching text is the explicit `No DROP statements` comment. Execution timestamp is **NOT DETERMINED** because it was not supplied.

**Next permitted operation:** first run the two read-only conflict `SELECT` statements at the top of `04-REFERENCE-CONFIG-DATA.sql` (unchanged, individually) after selecting `vintrack_dev`. Continue to execute the entire unmodified `04` file only if both result counts are `0`; otherwise stop and report the non-zero row(s). This extra read-only checkpoint is required because the approved file itself says to review conflicts before its backfill/configuration mutations.

**`04-REFERENCE-CONFIG-DATA.sql`: PASS BY OWNER-SUPPLIED phpMyAdmin EVIDENCE.** The pre-execution conflict checks returned `customer_menu_conflicts=0` and `admin_menu_conflicts=0`. The approved historical `normalized_value` backfill affected 4 plate-originated consultations and 42 remaining consultations. The global configuration row displays the approved values: deadline 3 days, maximum open 30 days, reuse 90 days, max pending 3, max files 8, max file bytes 3,145,728, timezone `America/Mexico_City`, reservation TTL 150 seconds. The approved menu and permission changes succeeded with the displayed insert/update counts, including no-op-safe zero inserts where an existing relationship already matched. No error, timeout or conflict is visible. Execution timestamp is **NOT DETERMINED** because it was not supplied.

**`05-MIGRATION-LEDGER-RECONCILIATION.sql`: PASS BY OWNER-SUPPLIED phpMyAdmin EVIDENCE.** The initial ledger result is `before_count=11` / `before_max_batch=2`; the final result is `after_count=55` / `after_max_batch=4`. The displayed batch-3 historical reconciliation and batch-4 bundle-marker inserts completed successfully through migration IDs 12 to 55, without error, timeout or ledger conflict visible. The observed target matches the approved 55 entries/max batch 4. Execution timestamp is **NOT DETERMINED** because it was not supplied.

**`06-POSTCHECK.sql`: PASS BY OWNER-SUPPLIED phpMyAdmin EVIDENCE.** Selected database is `vintrack_dev` on MariaDB 10.6.27. All structural target checks passed: 44 tables / 462 columns / 147 indexes / 54 foreign keys, zero non-InnoDB/unexpected-collation tables, and an empty missing-object result. The eight configuration columns are present with approved defaults. Aggregate anomalies are all zero: `invalid_config_rows=0`, `null_normalized_values=0`, `duplicate_case_numbers=0`. Ledger result is 55 / latest batch 4 / `ledger_ok=1`, and the duplicate-migration result is empty. The final operator-gate statement therefore passes. No other SQL is permitted; proceed only to staging application validation.
