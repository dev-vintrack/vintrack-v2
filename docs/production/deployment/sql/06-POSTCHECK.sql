-- VINTrack PG-01 exhaustive read-only postcheck.

SELECT DATABASE() AS selected_database, VERSION() AS database_version;
SELECT COUNT(*) AS table_count, COUNT(*)=44 AS table_count_ok,
       SUM(ENGINE<>'InnoDB') AS non_innodb_tables,
       SUM(TABLE_COLLATION<>'utf8mb4_unicode_ci') AS unexpected_collations
FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE';
SELECT COUNT(*) AS column_count, COUNT(*)=462 AS column_count_ok
FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE();
SELECT COUNT(DISTINCT CONCAT(TABLE_NAME,CHAR(0),INDEX_NAME)) AS index_count,
       COUNT(DISTINCT CONCAT(TABLE_NAME,CHAR(0),INDEX_NAME))=147 AS index_count_ok
FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE();
SELECT COUNT(*) AS foreign_key_count, COUNT(*)=54 AS foreign_key_count_ok
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE();

SELECT e.object_type,e.table_name,e.object_name
FROM (
 SELECT 'TABLE' object_type,x.table_name,x.table_name object_name FROM (
  SELECT 'consultation_operations' table_name UNION ALL SELECT 'notification_case_consultation_reservations' UNION ALL SELECT 'notification_case_document_scans' UNION ALL SELECT 'notification_case_documents' UNION ALL SELECT 'notification_case_events' UNION ALL SELECT 'notification_case_sequences' UNION ALL SELECT 'notification_case_source_guards' UNION ALL SELECT 'notification_case_user_guards' UNION ALL SELECT 'notification_case_vin_guards' UNION ALL SELECT 'notification_case_vin_reconciliations' UNION ALL SELECT 'notification_cases' UNION ALL SELECT 'notification_outbox' UNION ALL SELECT 'portal_notifications') x
 WHERE NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES t WHERE t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME=x.table_name)
 UNION ALL SELECT 'COLUMN','consultations','normalized_value' WHERE NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='consultations' AND COLUMN_NAME='normalized_value')
 UNION ALL SELECT 'INDEX','consultations','consultations_service_normalized_created_idx' WHERE NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='consultations' AND INDEX_NAME='consultations_service_normalized_created_idx')
 UNION ALL SELECT 'INDEX','consultations','consultations_normalized_created_idx' WHERE NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='consultations' AND INDEX_NAME='consultations_normalized_created_idx')
 UNION ALL SELECT 'INDEX','notification_case_documents','nc_documents_malware_queue_idx' WHERE NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notification_case_documents' AND INDEX_NAME='nc_documents_malware_queue_idx')
) e;

SELECT COLUMN_NAME,COLUMN_DEFAULT,IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='global_configuration'
  AND COLUMN_NAME IN ('notification_case_deadline_days','notification_case_max_open_days','notification_case_reuse_days','notification_case_max_pending','notification_case_max_files','notification_case_max_file_bytes','notification_case_timezone','notification_case_reservation_ttl_seconds')
ORDER BY ORDINAL_POSITION;
SELECT COUNT(*) AS invalid_config_rows FROM global_configuration
WHERE notification_case_deadline_days<>3 OR notification_case_max_open_days<>30 OR notification_case_reuse_days<>90
   OR notification_case_max_pending<>3 OR notification_case_max_files<>8 OR notification_case_max_file_bytes<>3145728
   OR notification_case_timezone<>'America/Mexico_City' OR notification_case_reservation_ttl_seconds<>150;
SELECT COUNT(*) AS null_normalized_values FROM consultations WHERE normalized_value IS NULL;
SELECT COUNT(*) AS duplicate_case_numbers FROM (SELECT case_number FROM notification_cases GROUP BY case_number HAVING COUNT(*)>1) d;
SELECT COUNT(*) AS migration_count,MAX(batch) AS latest_batch,COUNT(*)=55 AND MAX(batch)=4 AS ledger_ok FROM migrations;
SELECT migration,COUNT(*) AS occurrences FROM migrations GROUP BY migration HAVING COUNT(*)<>1;
SELECT 'PASS only when all *_ok=1, aggregate anomaly counts=0, missing-object result is empty, and eight config columns match' AS operator_gate;
