-- VINTrack PG-01: APPLY-ONCE additive schema delta.
-- Apply only after 01-PRECHECK returns every *_ok=1. No DROP statements.

CREATE TABLE `consultation_operations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned NOT NULL,
  `idempotency_key_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consultation_id` bigint unsigned DEFAULT NULL,
  `response_snapshot` text COLLATE utf8mb4_unicode_ci,
  `failure_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_started_at` datetime(6) DEFAULT NULL,
  `completed_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consult_ops_user_key_uq` (`user_id`,`idempotency_key_hash`),
  KEY `consult_ops_status_updated_idx` (`status`,`updated_at`),
  KEY `consultation_operations_provider_service_id_foreign` (`provider_service_id`),
  KEY `consultation_operations_consultation_id_foreign` (`consultation_id`),
  CONSTRAINT `consultation_operations_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `consultation_operations_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `consultation_operations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_sequences` (
  `year` smallint unsigned NOT NULL,
  `last_value` bigint unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_user_guards` (
  `user_id` bigint unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `notification_case_user_guards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_vin_guards` (
  `vin_key` char(17) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`vin_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_source_guards` (
  `source_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_consultation_reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `request_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime(6) NOT NULL,
  `consumed_at` datetime(6) DEFAULT NULL,
  `released_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_case_consultation_reservations_request_key_unique` (`request_key`),
  KEY `notification_reservations_live_idx` (`user_id`,`expires_at`,`consumed_at`,`released_at`),
  CONSTRAINT `notification_case_consultation_reservations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `consultation_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `previous_case_id` bigint unsigned DEFAULT NULL,
  `case_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vin` char(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vin_key` char(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recovery_place` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipality` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `neighborhood` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_number` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recovered_at` datetime(6) DEFAULT NULL,
  `license_plate` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_year` smallint unsigned DEFAULT NULL,
  `engine_number` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authority` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iph` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nuc` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `investigation_file` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `safekeeping` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inventory` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `notification_deadline_at` datetime NOT NULL,
  `opened_at` datetime(6) NOT NULL,
  `auto_close_at` datetime(6) NOT NULL,
  `submitted_at` datetime(6) DEFAULT NULL,
  `last_submitted_at` datetime(6) DEFAULT NULL,
  `review_started_at` datetime(6) DEFAULT NULL,
  `rejected_at` datetime(6) DEFAULT NULL,
  `validated_at` datetime(6) DEFAULT NULL,
  `closed_at` datetime(6) DEFAULT NULL,
  `lock_version` bigint unsigned NOT NULL DEFAULT '0',
  `creation_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_cases_consultation_id_unique` (`consultation_id`),
  UNIQUE KEY `notification_cases_case_number_unique` (`case_number`),
  UNIQUE KEY `notification_cases_creation_key_unique` (`creation_key`),
  KEY `notification_cases_previous_case_id_foreign` (`previous_case_id`),
  KEY `notification_cases_vin_applicable_idx` (`vin_key`,`status`,`validated_at`,`opened_at`,`id`),
  KEY `notification_cases_user_pending_idx` (`user_id`,`status`,`id`),
  KEY `notification_cases_auto_close_idx` (`status`,`auto_close_at`,`id`),
  KEY `notification_cases_deadline_idx` (`notification_deadline_at`,`status`,`id`),
  KEY `notification_cases_plate_idx` (`license_plate`,`id`),
  KEY `notification_cases_created_idx` (`created_at`,`id`),
  CONSTRAINT `notification_cases_consultation_id_foreign` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `notification_cases_previous_case_id_foreign` FOREIGN KEY (`previous_case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `notification_cases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_case_id` bigint unsigned NOT NULL,
  `uploaded_by_user_id` bigint unsigned NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_disk` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `removed_at` datetime(6) DEFAULT NULL,
  `removed_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_case_documents_storage_uq` (`storage_disk`,`storage_key`),
  KEY `notification_case_documents_active_idx` (`notification_case_id`,`removed_at`,`id`),
  KEY `notification_case_documents_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  KEY `notification_case_documents_removed_by_user_id_foreign` (`removed_by_user_id`),
  CONSTRAINT `notification_case_documents_notification_case_id_foreign` FOREIGN KEY (`notification_case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `notification_case_documents_removed_by_user_id_foreign` FOREIGN KEY (`removed_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `notification_case_documents_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_case_id` bigint unsigned DEFAULT NULL,
  `event_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `actor_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` datetime(6) NOT NULL,
  `from_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correlation_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_key` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_correlation_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_case_events_event_key_unique` (`event_key`),
  KEY `notification_case_events_timeline_idx` (`notification_case_id`,`occurred_at`,`id`),
  KEY `notification_case_events_type_idx` (`event_type`,`occurred_at`,`id`),
  KEY `notification_case_events_actor_user_id_foreign` (`actor_user_id`),
  CONSTRAINT `notification_case_events_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `notification_case_events_notification_case_id_foreign` FOREIGN KEY (`notification_case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_outbox` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `case_id` bigint unsigned DEFAULT NULL,
  `recipient_user_id` bigint unsigned NOT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dedup_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `available_at` datetime(6) NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `attempts` smallint unsigned NOT NULL DEFAULT '0',
  `locked_at` datetime(6) DEFAULT NULL,
  `locked_by` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` datetime(6) DEFAULT NULL,
  `failed_at` datetime(6) DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_outbox_dedup_key_unique` (`dedup_key`),
  KEY `notification_outbox_delivery_idx` (`status`,`available_at`,`id`),
  KEY `notification_outbox_case_id_foreign` (`case_id`),
  KEY `notification_outbox_recipient_user_id_foreign` (`recipient_user_id`),
  CONSTRAINT `notification_outbox_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `notification_outbox_recipient_user_id_foreign` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `portal_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `outbox_id` bigint unsigned NOT NULL,
  `recipient_user_id` bigint unsigned NOT NULL,
  `case_id` bigint unsigned DEFAULT NULL,
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portal_notifications_outbox_id_unique` (`outbox_id`),
  KEY `portal_notifications_inbox_idx` (`recipient_user_id`,`read_at`,`created_at`,`id`),
  KEY `portal_notifications_case_id_foreign` (`case_id`),
  CONSTRAINT `portal_notifications_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `portal_notifications_outbox_id_foreign` FOREIGN KEY (`outbox_id`) REFERENCES `notification_outbox` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `portal_notifications_recipient_user_id_foreign` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_case_vin_reconciliations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_case_id` bigint unsigned NOT NULL,
  `conflicting_case_id` bigint unsigned NOT NULL,
  `incident_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `detected_at` datetime(6) NOT NULL,
  `resolved_at` datetime(6) DEFAULT NULL,
  `resolved_by_user_id` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_case_vin_reconciliations_incident_key_unique` (`incident_key`),
  KEY `nc_vin_recon_case_fk` (`notification_case_id`),
  KEY `nc_vin_recon_conflict_fk` (`conflicting_case_id`),
  KEY `nc_vin_recon_resolver_fk` (`resolved_by_user_id`),
  CONSTRAINT `nc_vin_recon_case_fk` FOREIGN KEY (`notification_case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `nc_vin_recon_conflict_fk` FOREIGN KEY (`conflicting_case_id`) REFERENCES `notification_cases` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `nc_vin_recon_resolver_fk` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `global_configuration`
  ADD COLUMN `notification_case_deadline_days` smallint unsigned NOT NULL DEFAULT 3,
  ADD COLUMN `notification_case_max_open_days` smallint unsigned NOT NULL DEFAULT 30,
  ADD COLUMN `notification_case_reuse_days` smallint unsigned NOT NULL DEFAULT 90,
  ADD COLUMN `notification_case_max_pending` smallint unsigned NOT NULL DEFAULT 3,
  ADD COLUMN `notification_case_max_files` smallint unsigned NOT NULL DEFAULT 8,
  ADD COLUMN `notification_case_max_file_bytes` bigint unsigned NOT NULL DEFAULT 3145728,
  ADD COLUMN `notification_case_timezone` varchar(64) NOT NULL DEFAULT 'America/Mexico_City',
  ADD COLUMN `notification_case_reservation_ttl_seconds` smallint unsigned NOT NULL DEFAULT 150;

ALTER TABLE `consultations`
  ADD COLUMN `normalized_value` varchar(64) NULL AFTER `valor`;

ALTER TABLE `notification_case_documents`
  ADD COLUMN `malware_scan_status` varchar(16) NOT NULL DEFAULT 'PENDING' AFTER `sha256`,
  ADD COLUMN `malware_scan_attempts` smallint unsigned NOT NULL DEFAULT 0 AFTER `malware_scan_status`,
  ADD COLUMN `malware_scan_claim` varchar(64) NULL AFTER `malware_scan_attempts`,
  ADD COLUMN `malware_scan_claimed_at` datetime(6) NULL AFTER `malware_scan_claim`,
  ADD COLUMN `malware_scan_next_attempt_at` datetime(6) NULL AFTER `malware_scan_claimed_at`,
  ADD COLUMN `malware_scanned_at` datetime(6) NULL AFTER `malware_scan_next_attempt_at`;

CREATE TABLE `notification_case_document_scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_case_document_id` bigint unsigned NOT NULL,
  `attempt` smallint unsigned NOT NULL,
  `status` varchar(16) NOT NULL,
  `scanner` varchar(64) NOT NULL,
  `threat` varchar(191) NULL,
  `error_code` varchar(64) NULL,
  `started_at` datetime(6) NOT NULL,
  `finished_at` datetime(6) NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nc_document_scans_attempt_uq` (`notification_case_document_id`,`attempt`),
  KEY `nc_document_scans_status_idx` (`status`,`created_at`,`id`),
  CONSTRAINT `nc_document_scans_document_fk` FOREIGN KEY (`notification_case_document_id`) REFERENCES `notification_case_documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
