-- VINTrack Production Environment Verification Gate
-- Phase 1 read-only evidence pack. DO NOT EXECUTE without Class B authorization.
-- Select the VINTrack schema explicitly and redact account/host identifiers.

SELECT VERSION() AS database_version;

SELECT DATABASE() AS selected_database,
       @@version_comment AS database_distribution,
       @@character_set_server AS character_set_server,
       @@collation_server AS collation_server,
       @@global.time_zone AS global_time_zone,
       @@session.time_zone AS session_time_zone,
       @@system_time_zone AS system_time_zone,
       @@session.sql_mode AS session_sql_mode,
       @@max_allowed_packet AS max_allowed_packet,
       @@lower_case_table_names AS lower_case_table_names;

SHOW VARIABLES WHERE Variable_name IN (
    'character_set_client', 'character_set_connection',
    'character_set_database', 'character_set_results',
    'collation_connection', 'collation_database', 'event_scheduler',
    'innodb_strict_mode', 'max_connections', 'sql_mode',
    'system_time_zone', 'time_zone', 'transaction_isolation', 'tx_isolation'
);

SHOW ENGINES;

SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
FROM INFORMATION_SCHEMA.SCHEMATA
WHERE SCHEMA_NAME = DATABASE();

SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION, TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME,
       COLLATION, CARDINALITY, SUB_PART, INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME,
       REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION;

SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, UPDATE_RULE, DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT COUNT(*) AS applied_migration_count, MAX(batch) AS latest_migration_batch
FROM migrations;

SELECT migration, batch
FROM migrations
ORDER BY batch, migration;
