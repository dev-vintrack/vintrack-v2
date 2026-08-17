# Owner-supplied Production Schema Dump Assessment

**Date received/analyzed:** 2026-08-14
**Source file:** `vintrack_dev structure.sql`
**SHA-256:** `29a5aa66597a4d16fe6b0f91081718d5f34cf852782214782b691ffd78c50ccd`
**Analysis mode:** local text inspection only; not imported or executed

## Scope and Data Safety

The dump header identifies database `vintrack_dev`, server `localhost:3306`, phpMyAdmin 5.2.3 and server version `10.6.27-MariaDB-cll-lve-log`.

The artifact contains:

- DDL for 31 Laravel staging tables (`vintrack_dev`);
- indexes, auto-increment declarations and foreign keys;
- data rows only for `migrations`;
- no business-table `INSERT`;
- no trigger, procedure, function, event, view or `DEFINER` declaration.

The phpMyAdmin header reports PHP 8.4.24. This identifies phpMyAdmin's execution environment only; it does not prove the PHP version/handler used by the VINTrack domain and does not close the PHP runtime Gates.

## Database Identity and Physical Baseline

- schema: `vintrack_dev`;
- exact server: `10.6.27-MariaDB-cll-lve-log`;
- all 31 exported tables: InnoDB;
- all 31 exported tables: `utf8mb4` / `utf8mb4_unicode_ci`;
- dump session timezone: `+00:00` (export setting, not proof of application/session timezone policy);
- no non-InnoDB application table was found.

This supersedes the ambiguous `information_schema` metadata from the initial multi-query result for identity and exported DDL only.

## Migration Ledger

- applied ledger entries: 11;
- latest batch: 2;
- local migration files: 55;
- nominal ledger difference: 44 migration names.

Production ledger ends at:

```text
2026_07_08_000000_add_flags_json_to_consultations_table (batch 2)
```

Several tables/columns created by later repository migrations nevertheless exist in production. Therefore production contains historical/manual changes not reflected in the migration ledger. The 44-name difference must not be interpreted as 44 blindly runnable migrations.

## Table Drift

Production dump: 31 tables.
Versioned bootstrap: 43 tables.
Current migration model: 44 tables, including the later `notification_case_document_scans` table.

Missing from production:

```text
consultation_operations
notification_case_consultation_reservations
notification_case_document_scans
notification_case_documents
notification_case_events
notification_case_sequences
notification_case_source_guards
notification_case_user_guards
notification_case_vin_guards
notification_case_vin_reconciliations
notification_cases
notification_outbox
portal_notifications
```

No production-only table was found relative to the current expected model.

## Material Column and Index Drift

Material missing items in common tables:

- `consultations.normalized_value`;
- `consultations_normalized_created_idx`;
- `consultations_service_normalized_created_idx`;
- eight notification-case configuration columns in `global_configuration`:
  - `notification_case_deadline_days`;
  - `notification_case_max_open_days`;
  - `notification_case_reuse_days`;
  - `notification_case_max_pending`;
  - `notification_case_max_files`;
  - `notification_case_max_file_bytes`;
  - `notification_case_timezone`;
  - `notification_case_reservation_ttl_seconds`.

Most other detected differences are MariaDB dump syntax or historical constraint/index naming differences rather than demonstrated semantic loss. Examples include integer display widths, explicit per-column collation omission where table collation applies, MariaDB JSON-as-LONGTEXT/CHECK representation, and different names for equivalent foreign keys. These should be compared structurally, not repaired by name alone.

Production DDL counts from the dump:

- tables: 31;
- columns: 288;
- named/primary indexes: 94;
- foreign keys: 32.

Versioned bootstrap counts:

- tables: 43;
- columns: 446;
- named/primary indexes: 140;
- foreign keys: 53.

The current PG-05 additive migration adds one further table beyond that bootstrap.

## Compatibility Assessment

Positive evidence:

- the exact target DB is MariaDB 10.6.27;
- current production DDL is InnoDB/utf8mb4;
- MariaDB's JSON physical representation is expected and compatible with the exported legacy schema;
- no unsupported object type was discovered in the dump.

Unresolved/material risk:

- current application features depend on tables and columns absent from production;
- the migration ledger is not a trustworthy direct representation of all historical production changes;
- blindly running all ledger-pending migrations would risk collisions with already existing manually deployed structures;
- migration compatibility against a disposable MariaDB clone of this exact production structure has not yet been executed;
- application behavior against the target MariaDB runtime remains unverified.

## PG-01 Recommendation

**OWNER ACTION REQUIRED**

The engine/version identity portion is now verified, but PG-01 must not be marked CLOSED because material schema drift and migration-ledger divergence exist. No migration or production change is authorized.

## Next Minimum Action — Not Executed

Use this structure-only dump plus migration ledger to construct a disposable local MariaDB 10.6.27 clone, then perform an offline migration rehearsal with a reviewed reconciliation plan for ledger-versus-schema conflicts. This requires a separate authorization and an available isolated MariaDB 10.6.27 environment. It must not target production.
