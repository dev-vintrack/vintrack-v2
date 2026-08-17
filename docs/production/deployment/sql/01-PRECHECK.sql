-- VINTrack PG-01 deployment rehearsal: READ-ONLY PRECHECK.
-- Expected baseline: Owner dump SHA-256 29a5aa66597a4d16fe6b0f91081718d5f34cf852782214782b691ffd78c50ccd.
-- REVIEW EVERY RESULT. STOP if any *_ok value is 0 or any unexpected target object exists.

SELECT DATABASE() AS selected_database,
       DATABASE() = 'vintrack_dev' AS production_database_ok,
       DATABASE() LIKE 'vintrack_pg01_rehearsal_%' AS rehearsal_database_ok,
       VERSION() AS database_version,
       VERSION() LIKE '10.6.27-MariaDB%' AS target_version_ok;

SELECT COUNT(*) AS baseline_table_count,
       COUNT(*) = 31 AS baseline_table_count_ok,
       SUM(ENGINE <> 'InnoDB') AS non_innodb_tables,
       SUM(TABLE_COLLATION <> 'utf8mb4_unicode_ci') AS unexpected_collation_tables
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE';

SELECT COUNT(*) AS required_baseline_tables_present,
       COUNT(*) = 31 AS required_baseline_tables_ok
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('admin_menu_permissions','cache','cache_locks','consultations','credit_package_items','credit_packages','customer_menu_permissions','failed_jobs','global_configuration','inventory_movements','job_batches','jobs','menu_items','migrations','notification_deliveries','notification_policies','password_reset_tokens','provider_service_roles','provider_service_section_roles','provider_services','provider_services_sections','providers','purchase_items','role_types','roles','sessions','user_packages','user_provider_wallets','users','vehicles','wallet_ledger');

SELECT COUNT(*) AS provider_service_id_columns,
       COUNT(*) = 5 AS provider_service_id_baseline_ok
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME = 'provider_service_id'
  AND TABLE_NAME IN ('consultations','credit_package_items','user_provider_wallets','vehicles','wallet_ledger');

SELECT COUNT(*) AS unexpected_target_tables,
       COUNT(*) = 0 AS target_tables_absent_ok
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('consultation_operations','notification_case_consultation_reservations','notification_case_document_scans','notification_case_documents','notification_case_events','notification_case_sequences','notification_case_source_guards','notification_case_user_guards','notification_case_vin_guards','notification_case_vin_reconciliations','notification_cases','notification_outbox','portal_notifications');

SELECT COUNT(*) AS unexpected_target_columns,
       COUNT(*) = 0 AS target_columns_absent_ok
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME='consultations' AND COLUMN_NAME='normalized_value')
    OR (TABLE_NAME='global_configuration' AND COLUMN_NAME LIKE 'notification_case_%'));

SELECT COUNT(*) AS migration_count, MAX(batch) AS latest_batch,
       COUNT(*) = 11 AND MAX(batch) = 2 AS migration_baseline_ok
FROM migrations;

SELECT migration, batch FROM migrations ORDER BY id;

SELECT 'MANUAL GATE: continue only when every *_ok=1 and lists/counts match the rehearsal baseline' AS required_operator_action;
