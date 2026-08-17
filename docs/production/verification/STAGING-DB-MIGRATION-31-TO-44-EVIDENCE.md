# VINTrack — Staging Database Migration 31 → 44 Evidence

**Environment:** `vintrack_dev` / `dev.vintrack.com.mx` staging only
**Database engine:** `10.6.27-MariaDB-cll-lve-log`
**Execution method:** Owner-operated phpMyAdmin, separate approved SQL files
**Production / legacy:** not accessed or changed

## Preconditions

- Current approved Laravel code was already deployed to `public_html_dev`; public home booted on PHP 8.3.32.
- Staging `.env` and storage were preserved.
- Private Owner backup `vintrack_dev (1).sql.gz`: 27,832 bytes, SHA-256 `0866c11813e4e125be64440de10fbd8b36b2a73eac5f83c77da2dcf1daea9f94`; gzip readability and non-sensitive schema/data markers verified locally. No restore was performed.
- Local regression immediately before execution: **116 tests / 545 assertions / 0 failures**.

## Approved bundle integrity

The six unchanged files under `docs/production/deployment/sql/` match the validated MySQL 8.4.3 and MariaDB 10.6.27 rehearsal hashes recorded in `STAGING-DB-MIGRATION-EXECUTION-CHECKLIST.md`.

## Execution results

| File | Result | Evidence |
|---|---|---|
| 01-PRECHECK | PASS | Correct `vintrack_dev`; MariaDB 10.6.27; 31 InnoDB/utf8mb4 tables; required baseline and five provider-service columns present; target objects absent; ledger 11 / batch 2. |
| 02-SCHEMA-DELTA | PASS | Additive target schema created, including 13 target tables, settings columns, `consultations.normalized_value`, malware columns and scan history table. No visible SQL/FK/timeout failure. |
| 03-INDEXES-CONSTRAINTS | PASS | Normalized consultation indexes completed in ~0.0641 s; malware queue index in ~0.0219 s. |
| 04-REFERENCE-CONFIG-DATA | PASS | Menu conflicts 0/0; normalized-value backfill 4 plate rows + 42 remaining rows; approved settings/menu/permissions applied. |
| 05-MIGRATION-LEDGER-RECONCILIATION | PASS | Ledger 11 / batch 2 → 55 / batch 4; no visible conflict. |
| 06-POSTCHECK | PASS | Target counts and all aggregate anomaly checks passed. |

Owner screenshots did not provide wall-clock start/end timestamps for the individual files, so those durations are **NOT DETERMINED** except for the phpMyAdmin query durations explicitly visible above.

## Final postcheck target

- 44 tables, `table_count_ok=1`; 0 non-InnoDB; 0 unexpected collations.
- 462 columns, `column_count_ok=1`.
- 147 indexes, `index_count_ok=1`.
- 54 foreign keys, `foreign_key_count_ok=1`.
- Missing-target-object result: empty.
- Eight notification configuration columns exist, are NOT NULL and have the approved defaults.
- `invalid_config_rows=0`.
- `null_normalized_values=0`.
- `duplicate_case_numbers=0`.
- Ledger: 55, latest batch 4, `ledger_ok=1`; duplicate migration result empty.

## Data preservation and normalized value

The approved 04 backfill populated all 46 previously null `normalized_value` values. No reset, truncate, delete, database rebuild or restore occurred. The SQL bundle is apply-once and must not be repeated; `01-PRECHECK.sql` now correctly rejects the transformed baseline.

## Next validation boundary

Schema migration is complete. Staging application validation remains required before declaring a Golden Staging Candidate: public Laravel health, Owner authentication/session/logout, authorized Client/Admin pages, histories/DataTables, notification-case and Evidence UI schema compatibility, and portal-notification UI. No provider call, SMTP, Cron, upload/writability probe, malware provider, production action or `vintrack_app` clone is authorized by this evidence.

### Public health after migration

Read-only public checks after POSTCHECK passed:

- `https://dev.vintrack.com.mx/` rendered the expected VINTrack Laravel home page over HTTPS (`Inicio - VINTRACK`) with no visible blank page, stack trace or framework boot error.
- `https://dev.vintrack.com.mx/login` rendered the expected email/password login form (`Iniciar sesión - VINTRACK`).
- Browser console captured no warning or error on either page.

No Owner credentials were requested, entered or handled by Codex. Authentication, authorized role menus, histories/DataTables and schema-module pages remain Owner-operated validation steps.

## Owner-operated authenticated validation — PASS

The Owner completed the authorized staging validation after the 44-table POSTCHECK and attested to all checks below. Screenshot evidence was reviewed without recording authentication secrets, personal emails, VIN values or any other sensitive input.

- **Authentication:** login completed; the OTP verification screen rendered and authentication completed; authenticated session, expected role menu and logout all passed.
- **Client role:** Portal Cliente, credits/movements, `Historial de Vehículos Consultados`, server-side DataTables/filter/search/pagination, VIN Decoder view, `Proceso de Notificaciones`, expediente/Evidence views without upload, and Portal Notifications loaded correctly. The notification-process empty state was displayed correctly where no expedient existed.
- **Admin role:** `Historial Global de Vehículos Consultados` loaded, displayed paginated records and accepted the specific-client filter; Notification Cases, Evidence UI and Portal Notifications were confirmed by the Owner to load correctly. No validation/rejection or other irreversible operation was performed.
- **Schema-dependent compatibility:** Notification Cases, Evidence/malware-schema-dependent views, histories and portal-notification views loaded against the new tables. No malware provider, scan, upload or external integration was configured or invoked.
- **Safety:** no consultation/provider call, credit consumption, evidence upload, SMTP delivery, Cron, migration, additional SQL, manual data creation or production/legacy action occurred during the validation.

## Golden Staging recommendation

All authorized Golden Staging Candidate criteria are now supported by evidence: current approved code; PHP 8.3.32 evidence; 44-table target and POSTCHECK PASS; data-preserving bundle completion; Laravel boot; authentication/session/logout; role-scoped client/admin pages; histories/DataTables; and schema-dependent module loading without a material staging defect.

**Recommendation: GOLDEN STAGING CANDIDATE — READY FOR OWNER REVIEW.** This is not production authorization, does not close PG-06, and does not authorize `vintrack_app` creation/cloning, production deployment, SMTP/Cron, malware-provider integration or any additional write probe.
