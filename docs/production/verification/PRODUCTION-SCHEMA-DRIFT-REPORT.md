# Production Schema Drift Report

## Evidence

- Owner dump: `vintrack_dev structure.sql`.
- SHA-256: `29a5aa66597a4d16fe6b0f91081718d5f34cf852782214782b691ffd78c50ccd`.
- Target intent: 55 repository migrations plus current versioned schema.
- Production: MariaDB `10.6.27-MariaDB-cll-lve-log`, 31 InnoDB/utf8mb4 tables, 11 ledger entries, max batch 2.

## Canonical Delta

Current tables: 31. Target tables: 44. Missing tables: 13:

`consultation_operations`, `notification_case_consultation_reservations`, `notification_case_document_scans`, `notification_case_documents`, `notification_case_events`, `notification_case_sequences`, `notification_case_source_guards`, `notification_case_user_guards`, `notification_case_vin_guards`, `notification_case_vin_reconciliations`, `notification_cases`, `notification_outbox`, `portal_notifications`.

The former 12-table partial bundle omitted `notification_case_document_scans`; **PARTIAL BUNDLE OMISSION — CORRECTED**.

New columns beyond the 13 table definitions:

- `global_configuration`: `notification_case_deadline_days=3`, `notification_case_max_open_days=30`, `notification_case_reuse_days=90`, `notification_case_max_pending=3`, `notification_case_max_files=8`, `notification_case_max_file_bytes=3145728`, `notification_case_timezone='America/Mexico_City'`, `notification_case_reservation_ttl_seconds=150`;
- `consultations.normalized_value varchar(64) NULL` plus required backfill;
- `notification_case_documents`: six malware scan state/claim/timestamp columns.

New target totals demonstrated by rehearsal: 462 columns, 147 distinct table/index names and 54 FKs. Existing equivalent constraints with historical names are preserved; the bundle does not recreate or rename them.

## Reference and Configuration Data

The eight notification settings are columns on the existing singleton `global_configuration`, not key/value rows. Their NOT NULL defaults populate any existing row without overwriting unrelated settings. The structure-only clone contains zero configuration rows, so defaults were verified through metadata; effects on real row volume require a predeployment backup and read-only value review.

Menu migrations add two notification routes and eligible role permissions, update two history labels/icons, grant admin/analyst history and disable support history. Conflict queries precede inserts. New route rows use insert-if-absent; conflicting existing definitions require operator STOP rather than silent overwrite.

## Ledger Drift and Strategy

- 11 `APPLIED_REGISTERED`, batches 1–2;
- 34 `APPLIED_UNREGISTERED`, assigned reconciliation batch 3 only after baseline checks;
- 10 `NOT_APPLIED_REQUIRED`, assigned deployment batch 4 only after delta/reference/postchecks;
- final rehearsal ledger: 55 unique names, max batch 4.

Batches 3 and 4 are explicit operational markers, not invented historical execution batches. Prior history remains FORWARD-ONLY; recovery remains backup/restore or forward-fix, never assumed historical Laravel rollback.

## SQL Safety Review

No `DROP`, `TRUNCATE` or `DELETE` appears in the bundle. DDL is additive. Risks requiring real-data planning:

- 13 CREATE TABLE statements and ALTERs can acquire metadata locks;
- adding eight NOT NULL/default columns and malware columns may rebuild tables on MariaDB depending on row size/algorithm;
- normalized-value backfill is a deliberate bounded-scope mass UPDATE over all consultations and may be long-running;
- two new consultation indexes can require substantial time/storage and lock resources;
- FK creation for new empty tables is low-data risk but depends on existing referenced types/indexes;
- menu updates are route-scoped; conflicts must stop;
- charset/collation remain InnoDB/utf8mb4_unicode_ci;
- DATETIME(6) fields preserve contractual application timezone semantics without implicit TIMESTAMP conversion.

No production execution is authorized.
