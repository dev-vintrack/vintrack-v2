# Class B #1 — Read-only Database Verification Evidence

**Authorization:** Production Environment Verification Gate — Phase 2, Class B #1
**Date:** 2026-08-14
**Selected schema:** `vintrack_dev`
**Owner visual confirmation:** received before execution
**SQL artifact SHA-256:** `fed9690bddf7069f77eab3d0c5dbb637f64426ddcfac5fd03a6e72b818e585dd`
**Execution start (UTC):** `2026-08-14T20:20:47.101Z`
**Execution end (UTC):** `2026-08-14T20:20:48.708Z`
**Execution attempts reaching the server:** 1
**Mutating statements:** none

## Execution Qualification

An earlier UI submission produced the client-side message `Missing value in the form!`; the Owner confirmed that the SQL editor was empty and no statement was executed. That validation event is not counted as a database execution.

The approved artifact was then loaded into the actual CodeMirror editor. Before submission:

- the page breadcrumb identified `Database: vintrack_dev`;
- the editor content normalized to the approved artifact SHA-256;
- no prohibited statement start was detected locally;
- the package was submitted exactly once.

## Result

The multi-statement request did not yield a complete usable evidence set in phpMyAdmin. Its final authorized statement failed with:

```text
#1109 - Unknown table 'migrations' in information_schema
```

The failed statement was the approved read-only migration count. No retry, corrected query or additional SQL was executed.

Because phpMyAdmin retained only the terminal error view, the result page did not provide reproducible values for the earlier result sets. Therefore the following requested facts remain **NOT DETERMINED** from this execution:

- exact MariaDB version/distribution;
- server/default charset and collation variables;
- database/global/session timezone;
- SQL mode and isolation;
- `max_allowed_packet`;
- complete engine result;
- complete column/index/constraint/FK result sets;
- applied migration count and latest batch;
- ordered production migration ledger.

No value is inferred or invented for those fields.

## Schema Drift Evidence Available from the Confirmed phpMyAdmin Structure

Before submission, the confirmed `vintrack_dev` structure page exposed 31 tables:

```text
admin_menu_permissions
cache
cache_locks
consultations
credit_package_items
credit_packages
customer_menu_permissions
failed_jobs
global_configuration
inventory_movements
job_batches
jobs
menu_items
migrations
notification_deliveries
notification_policies
password_reset_tokens
provider_service_roles
provider_service_section_roles
provider_services
provider_services_sections
providers
purchase_items
role_types
roles
sessions
user_packages
user_provider_wallets
users
vehicles
wallet_ledger
```

The current local migration model expects 44 application tables. Thirteen expected tables were not visible in production:

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

This is material schema drift. It is consistent with SPRINT-02/03/07/08 and PG-05 schema not being deployed; it is not evidence that migrations should now be executed. Production migrations and deployment remain unauthorized.

Local baseline detail:

- current migration files: 55;
- current versioned `database/schema/mysql-schema.sql`: 43 tables;
- current migration model: 44 tables because `notification_case_document_scans` is additive after the recorded bootstrap.

The bootstrap/migration count difference is a local baseline observation to reconcile before a future deployment artifact is approved. It does not change production.

## Compatibility Assessment

- phpMyAdmin labels the server family as MariaDB, but the exact server version was not reproducibly returned; MariaDB `10.6.27` is **NOT VERIFIED** by this execution.
- Visible application table rows were reported as InnoDB with `utf8mb4_unicode_ci`, but the incomplete result does not justify a schema-wide engine/collation conclusion.
- Material table drift prevents closing PG-01 even if the exact runtime later proves compatible.
- Pending production migrations are evident at table level, but their exact ledger boundary/count/latest batch remains **NOT DETERMINED**.

## PG-01 Recommendation

**OWNER ACTION REQUIRED**

PG-01 must not be marked CLOSED. Preserve its current external/unresolved status until a separately reviewed, compact supplemental read-only pack returns database identity/configuration and a schema-qualified migration ledger, and the Owner decides how material schema drift will be handled within a separately authorized deployment process.

## Next Minimum Action — Not Executed

Prepare and request Owner authorization for Class B #2: one compact, read-only supplemental SQL pack that:

1. confirms `DATABASE()` first and stops operationally on mismatch;
2. returns version/configuration in small result sets;
3. qualifies the migration ledger with the confirmed schema name;
4. returns aggregate table/index/FK counts plus the missing-table comparison without business rows;
5. is designed for phpMyAdmin result retention/export.

No Class B #2 action has been executed or authorized.

## Owner-supplied Follow-up Evidence

The Owner subsequently supplied a structure-only `vintrack_dev` dump with data limited to the `migrations` ledger. Local text analysis resolved the database identity ambiguity without executing further production queries:

- exact server: `10.6.27-MariaDB-cll-lve-log`;
- production schema: 31 InnoDB tables, all `utf8mb4_unicode_ci`;
- migration ledger: 11 entries, latest batch 2;
- material drift: 13 expected tables absent plus notification/idempotency columns and indexes absent;
- ledger/schema divergence: several post-ledger structures exist despite their migration names not being recorded.

The detailed superseding assessment is `docs/production/verification/OWNER-SUPPLIED-SCHEMA-DUMP-ASSESSMENT.md`. PG-01 remains `OWNER ACTION REQUIRED`; it is not CLOSED.
