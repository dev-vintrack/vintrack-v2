# Production Schema Reconciliation Matrix

**Baseline:** Owner-supplied `vintrack_dev` dump, SHA-256 `29a5aa66597a4d16fe6b0f91081718d5f34cf852782214782b691ffd78c50ccd`
**Target:** current approved repository, 55 migration files
**Historical cutoff:** `2026_08_10_210000_add_provider_service_id_to_consultations_table.php` inclusive

Classification totals: `APPLIED_REGISTERED=11`, `APPLIED_UNREGISTERED=34`, `NOT_APPLIED_REQUIRED=10`, `SUPERSEDED_OR_EQUIVALENT=0`, `NOT_APPLICABLE=0`, `REQUIRES_OWNER_DECISION=0`.

## Baseline Correction Trace

The initial assessment correctly identified 13 absent target tables. The first partial `02-SCHEMA-DELTA.sql` contained only 12 because `notification_case_document_scans` was accidentally omitted; it was never present or superseded. Owner correction authorization restored it from migration `2026_08_14_120000_add_malware_scanning_to_notification_case_documents.php`. Status: **PARTIAL BUNDLE OMISSION — CORRECTED**. Current/target/delta are 31/44/13.

Evidence codes:

- `L`: present in the production `migrations` ledger.
- `D`: material schema effect present in the production dump.
- `O`: Owner contractual historical assertion through the cutoff.
- `A`: absent from the production dump and after the cutoff.
- `R`: repository migration/source-of-intent inspection.

| # | Migration | Intended effect: tables / columns / indexes / FKs / data | Ledger | Production effect | Classification | Evidence | Future action |
|---:|---|---|---:|---|---|---|---|
| 1 | `0001_01_01_000000_create_users_table.php` | Create `users`, `password_reset_tokens`, `sessions`; email/session indexes. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 2 | `0001_01_01_000001_create_cache_table.php` | Create `cache`, `cache_locks` with PKs. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 3 | `0001_01_01_000002_create_jobs_table.php` | Create `jobs`, `job_batches`, `failed_jobs`; queue/UUID indexes. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 4 | `2024_07_06_180000_create_providers_table.php` | Create `providers`; unique provider code. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 5 | `2024_07_06_180001_create_provider_services_table.php` | Create `provider_services`; provider FK; provider/key unique. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 6 | `2024_07_06_180002_add_profile_fields_to_users_table.php` | Add name/phone/role/status/approval profile fields. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 7 | `2024_07_06_180003_create_user_provider_wallets_table.php` | Create wallets; user/provider FKs and unique relation. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 8 | `2024_07_06_180004_create_wallet_ledger_table.php` | Create wallet ledger; wallet FK; correlation unique. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 9 | `2024_07_07_120000_add_credit_cost_to_provider_services_table.php` | Add service `credit_cost`. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 10 | `2024_07_07_120001_create_consultations_table.php` | Create consultations; user/provider FKs and search indexes. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 11 | `2026_07_08_000000_add_flags_json_to_consultations_table.php` | Add `flags_json`. | yes | present | APPLIED_REGISTERED | L,D | None. |
| 12 | `2026_07_10_000001_create_credit_packages_table.php` | Create packages/items/user assignments with FKs. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger after precheck. |
| 13 | `2026_07_13_000000_update_users_roles_and_status.php` | Normalize user role/status fields. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 14 | `2026_07_13_000001_create_admin_menu_permissions_table.php` | Create admin route permissions and indexes. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 15 | `2026_07_14_000000_create_vehicles_table.php` | Create reporting `vehicles`; provider/value unique and lookup index. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger; remains non-master. |
| 16 | `2026_07_17_000000_create_purchase_items_table.php` | Create purchases; provider/service/admin FKs and status indexes. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 17 | `2026_07_17_000001_create_provider_services_sections_table.php` | Create service sections; FK and service/section unique. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 18 | `2026_07_17_000002_create_provider_service_section_roles_table.php` | Create section-role permissions; section FK and unique. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 19 | `2026_07_17_000003_add_available_credits_to_provider_services_table.php` | Add available-credit counter. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 20 | `2026_07_17_000004_restructure_provider_services_for_placas_service.php` | Seed/restructure Placas service sections and roles. | no | Owner-confirmed; structural dependencies present | APPLIED_UNREGISTERED | O,D | Precheck reference invariants; reconcile ledger. |
| 21 | `2026_07_17_000005_add_provider_service_id_to_user_provider_wallets_table.php` | Add/backfill service FK; replace provider uniqueness. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 22 | `2026_07_17_000006_add_provider_service_id_to_wallet_ledger_table.php` | Add/backfill service FK. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 23 | `2026_07_17_000007_add_provider_service_id_to_credit_package_items_table.php` | Add/backfill service FK. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 24 | `2026_07_17_000008_add_provider_service_id_to_vehicles_table.php` | Add/backfill service FK; service/value unique. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 25 | `2026_07_20_000000_create_inventory_movements_table.php` | Create inventory audit movements and FKs/indexes. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 26 | `2026_07_21_000001_add_min_alert_columns_to_provider_services_table.php` | Add client/admin alert thresholds. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 27 | `2026_07_21_190000_create_roles_table.php` | Create roles and seed canonical roles. | no | present / Owner-confirmed data | APPLIED_UNREGISTERED | D,O | Validate role keys; reconcile ledger. |
| 28 | `2026_07_21_190001_add_id_rol_to_users_table.php` | Add/backfill role FK; remove legacy role text. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 29 | `2026_07_21_190002_add_id_rol_to_admin_menu_permissions_table.php` | Replace role text with role FK; rebuild uniques/index. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 30 | `2026_07_21_190003_add_id_rol_to_provider_service_section_roles_table.php` | Replace role text with role FK and unique. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 31 | `2026_07_21_200000_create_provider_service_roles_table.php` | Create service-role permissions, FKs, unique; seed rows. | no | present / Owner-confirmed data | APPLIED_UNREGISTERED | D,O | Validate relationships; reconcile ledger. |
| 32 | `2026_07_23_033124_create_role_types_table.php` | Create role types and unique name. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 33 | `2026_07_23_033142_add_role_type_id_and_meta_to_roles_table.php` | Add role type FK, home route, approval flag. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 34 | `2026_07_23_101000_add_status_to_user_packages_and_wallets_tables.php` | Add status fields. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 35 | `2026_07_24_200000_create_global_configuration_table.php` | Create singleton configuration and seed defaults. | no | present / Owner-confirmed data | APPLIED_UNREGISTERED | D,O | Validate singleton; reconcile ledger. |
| 36 | `2026_07_27_131900_add_package_price_fields_to_global_configuration_table.php` | Add/update package price/validity configuration. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 37 | `2026_07_27_210000_create_notification_policies_and_deliveries_tables.php` | Create delivery policy/log tables, FKs/indexes; seed policies/menu permission. | no | present / Owner-confirmed data | APPLIED_UNREGISTERED | D,O | Validate keys; reconcile ledger. |
| 38 | `2026_07_28_200000_create_customer_menu_permissions_table.php` | Create customer menu permissions and role FK/indexes. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 39 | `2026_07_28_230000_add_otp_fields_to_users_table.php` | Add OTP/police-entity fields. | no | present | APPLIED_UNREGISTERED | D,O | Reconcile ledger. |
| 40 | `2026_07_31_000001_add_adapter_code_to_providers_table.php` | Add/backfill unique `adapter_code`. | no | present | APPLIED_UNREGISTERED | D,O | Validate non-null business mappings; reconcile ledger. |
| 41 | `2026_07_31_000002_add_service_code_to_provider_services_table.php` | Add/backfill unique `service_code`. | no | present | APPLIED_UNREGISTERED | D,O | Validate mappings; reconcile ledger. |
| 42 | `2026_08_04_000001_add_vin_decoder_to_customer_menu_permissions.php` | Upsert VIN decoder customer route permission. | no | Owner-confirmed | APPLIED_UNREGISTERED | O | Precheck reference key; reconcile ledger. |
| 43 | `2026_08_05_000001_create_menu_items_table.php` | Create canonical menu catalog/index and seed discovered routes. | no | present / Owner-confirmed data | APPLIED_UNREGISTERED | D,O | Validate route keys; reconcile ledger. |
| 44 | `2026_08_05_000002_drop_label_icon_from_menu_permissions_tables.php` | Remove duplicated label/icon columns. | no | effect present | APPLIED_UNREGISTERED | D,O | Reconcile ledger; forward-only. |
| 45 | `2026_08_10_210000_add_provider_service_id_to_consultations_table.php` | Add/backfill non-null service FK and service identity index. | no | present | APPLIED_UNREGISTERED | D,O | Validate no mismatch/null; reconcile ledger. |
| 46 | `2026_08_12_220000_add_notification_case_settings_to_global_configuration.php` | Add eight 3/30/90/files/timezone/reservation settings. | no | absent | NOT_APPLIED_REQUIRED | A,R | Apply additive columns with defaults. |
| 47 | `2026_08_12_220100_create_notification_case_guards_and_sequences.php` | Create five sequence/guard/reservation tables, user FKs and live index. | no | absent | NOT_APPLIED_REQUIRED | A,R | Create via delta then indexes/FKs. |
| 48 | `2026_08_12_220200_create_notification_cases_table.php` | Create case aggregate, folio/creation uniques, historical FKs and domain indexes. | no | absent | NOT_APPLIED_REQUIRED | A,R | Create after guards, then constraints. |
| 49 | `2026_08_12_220300_create_notification_case_support_tables.php` | Create documents/events/outbox/portal/reconciliation tables and constraints. | no | absent | NOT_APPLIED_REQUIRED | A,R | Create in dependency order. |
| 50 | `2026_08_13_120000_add_notification_process_customer_menu.php` | Upsert customer menu item/permissions for eligible client roles. | no | not demonstrable; post-cutoff | NOT_APPLIED_REQUIRED | A,R | Apply guarded reference data. |
| 51 | `2026_08_13_180000_add_notification_process_admin_menu.php` | Upsert admin menu item/permissions for admin/analyst. | no | not demonstrable; post-cutoff | NOT_APPLIED_REQUIRED | A,R | Apply guarded reference data. |
| 52 | `2026_08_14_000000_update_vehicle_consultation_history_menus.php` | Rename history menu labels and grant analyst/support access. | no | not demonstrable; post-cutoff | NOT_APPLIED_REQUIRED | A,R | Apply guarded reference data. |
| 53 | `2026_08_14_120000_add_malware_scanning_to_notification_case_documents.php` | Add six scan-state columns/queue index; create scan-attempt table/FK/indexes. | no | absent | NOT_APPLIED_REQUIRED | A,R | Apply after Evidence tables. |
| 54 | `2026_08_14_120000_create_consultation_operations_table.php` | Create persistent idempotency operations with unique key/FKs/status index. | no | absent | NOT_APPLIED_REQUIRED | A,R | Create table and constraints. |
| 55 | `2026_08_14_130000_add_normalized_value_to_consultations.php` | Add/backfill normalized identity; add two history indexes. | no | absent | NOT_APPLIED_REQUIRED | A,R | Add nullable column, bounded-risk backfill, indexes. |

## Reconciliation Decision

No pre-cutoff migration materially contradicts the structural dump. Historical reference-data effects not present in a structure-only dump rely on the explicit Owner assertion and must receive read-only key checks before ledger reconciliation. The classification does not authorize production SQL.

The final revalidation retained all counts. Structural effects through `consultations.provider_service_id` are present. Reference-data-only effects cannot be proven from a structure-only dump; for those, concrete table/column prerequisites plus the contractual Owner history are recorded, and the bundle adds read-only conflict/key checks before reconciliation.
