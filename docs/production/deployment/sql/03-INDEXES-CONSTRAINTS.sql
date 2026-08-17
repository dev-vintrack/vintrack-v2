-- VINTrack PG-01: indexes on altered existing/new tables not already created in 02.
-- APPLY ONCE after 02-SCHEMA-DELTA.sql. No DROP statements.

ALTER TABLE `consultations`
  ADD KEY `consultations_service_normalized_created_idx` (`provider_service_id`,`normalized_value`,`criterio`,`created_at`,`id`),
  ADD KEY `consultations_normalized_created_idx` (`normalized_value`,`created_at`,`id`);

ALTER TABLE `notification_case_documents`
  ADD KEY `nc_documents_malware_queue_idx` (`malware_scan_status`,`malware_scan_next_attempt_at`,`id`);
