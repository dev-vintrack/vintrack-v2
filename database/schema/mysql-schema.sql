/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admin_menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_menu_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` bigint unsigned DEFAULT NULL,
  `route_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_menu_permissions_id_rol_route_name_unique` (`id_rol`,`route_name`),
  KEY `admin_menu_permissions_id_rol_enabled_display_order_index` (`id_rol`,`enabled`,`display_order`),
  CONSTRAINT `admin_menu_permissions_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultation_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned NOT NULL,
  `criterio` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'placa, niv, vin',
  `valor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_value` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `services` json DEFAULT NULL,
  `costo_credito` decimal(10,2) NOT NULL DEFAULT '0.00',
  `http_status_post` int DEFAULT NULL,
  `http_status_get` int DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alerta_robo` tinyint(1) NOT NULL DEFAULT '0',
  `repuve_robo` tinyint(1) NOT NULL DEFAULT '0',
  `pgj_robo` tinyint(1) NOT NULL DEFAULT '0',
  `ocra_robo` tinyint(1) NOT NULL DEFAULT '0',
  `carfax_robo` tinyint(1) NOT NULL DEFAULT '0',
  `rapi_robo` tinyint(1) NOT NULL DEFAULT '0',
  `flags_json` json DEFAULT NULL,
  `response_json` json DEFAULT NULL,
  `credits_api` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultations_provider_id_foreign` (`provider_id`),
  KEY `consultations_user_id_index` (`user_id`),
  KEY `consultations_valor_index` (`valor`),
  KEY `consultations_criterio_index` (`criterio`),
  KEY `consultations_service_criterio_valor_created_idx` (`provider_service_id`,`criterio`,`valor`,`created_at`),
  KEY `consultations_service_normalized_created_idx` (`provider_service_id`,`normalized_value`,`criterio`,`created_at`,`id`),
  KEY `consultations_normalized_created_idx` (`normalized_value`,`created_at`,`id`),
  CONSTRAINT `consultations_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `consultations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_package_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_package_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_package_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned DEFAULT NULL,
  `credits` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_package_items_credit_package_id_foreign` (`credit_package_id`),
  KEY `credit_package_items_provider_id_foreign` (`provider_id`),
  KEY `credit_package_items_provider_service_id_foreign` (`provider_service_id`),
  CONSTRAINT `credit_package_items_credit_package_id_foreign` FOREIGN KEY (`credit_package_id`) REFERENCES `credit_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_package_items_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_package_items_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `validity_days` int unsigned NOT NULL DEFAULT '30',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_menu_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_menu_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` bigint unsigned NOT NULL,
  `route_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_menu_permissions_id_rol_route_name_unique` (`id_rol`,`route_name`),
  KEY `customer_menu_permissions_id_rol_enabled_display_order_index` (`id_rol`,`enabled`,`display_order`),
  CONSTRAINT `customer_menu_permissions_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `global_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_configuration` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `min_purchase_user` decimal(10,2) NOT NULL DEFAULT '10.00',
  `max_purchase_user` decimal(10,2) NOT NULL DEFAULT '1000.00',
  `step_purchase_input` decimal(10,2) NOT NULL DEFAULT '10.00',
  `min_price_package` decimal(10,2) NOT NULL DEFAULT '100.00',
  `max_price_package` decimal(10,2) NOT NULL DEFAULT '5000.00',
  `step_price_package` decimal(10,2) NOT NULL DEFAULT '100.00',
  `min_validity_days` int unsigned NOT NULL DEFAULT '30',
  `max_validity_days` int unsigned NOT NULL DEFAULT '360',
  `step_validity_input` int unsigned NOT NULL DEFAULT '30',
  `package_blocks_direct_credit` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `notification_case_deadline_days` smallint unsigned NOT NULL DEFAULT '3',
  `notification_case_max_open_days` smallint unsigned NOT NULL DEFAULT '30',
  `notification_case_reuse_days` smallint unsigned NOT NULL DEFAULT '90',
  `notification_case_max_pending` smallint unsigned NOT NULL DEFAULT '3',
  `notification_case_max_files` smallint unsigned NOT NULL DEFAULT '8',
  `notification_case_max_file_bytes` bigint unsigned NOT NULL DEFAULT '3145728',
  `notification_case_timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Mexico_City',
  `notification_case_reservation_ttl_seconds` smallint unsigned NOT NULL DEFAULT '150',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_service_id` bigint unsigned NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `admin_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_admin_id_foreign` (`admin_id`),
  KEY `inventory_movements_provider_service_id_index` (`provider_service_id`),
  KEY `inventory_movements_type_index` (`type`),
  KEY `inventory_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `inventory_movements_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_items_route_name_unique` (`route_name`),
  KEY `menu_items_scope_index` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_consultation_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_case_sequences` (
  `year` smallint unsigned NOT NULL,
  `last_value` bigint unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_source_guards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_case_source_guards` (
  `source_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`source_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_user_guards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_case_user_guards` (
  `user_id` bigint unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `notification_case_user_guards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_vin_guards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_case_vin_guards` (
  `vin_key` char(17) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  PRIMARY KEY (`vin_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_case_vin_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `provider_service_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_type` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bcc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` smallint unsigned NOT NULL DEFAULT '0',
  `dedup_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `error` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_deliveries_uuid_unique` (`uuid`),
  UNIQUE KEY `notification_deliveries_dedup_key_unique` (`dedup_key`),
  KEY `notification_deliveries_event_type_status_index` (`event_type`,`status`),
  KEY `notification_deliveries_provider_service_id_created_at_index` (`provider_service_id`,`created_at`),
  KEY `notification_deliveries_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `notification_deliveries_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notification_deliveries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_outbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_service_id` bigint unsigned NOT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `low_balance_threshold` decimal(10,2) DEFAULT NULL,
  `expiring_days` json DEFAULT NULL,
  `cooldown_hours` int unsigned NOT NULL DEFAULT '72',
  `bcc_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_policy_service_event_unique` (`provider_service_id`,`event_type`),
  KEY `notification_policies_event_type_enabled_index` (`event_type`,`enabled`),
  CONSTRAINT `notification_policies_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provider_service_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_service_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `psr_role_service_unique` (`id_rol`,`provider_service_id`),
  KEY `provider_service_roles_provider_service_id_foreign` (`provider_service_id`),
  CONSTRAINT `provider_service_roles_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  CONSTRAINT `provider_service_roles_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provider_service_section_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_service_section_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_service_section_id` bigint unsigned NOT NULL,
  `id_rol` bigint unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pss_id_rol_unique` (`provider_service_section_id`,`id_rol`),
  KEY `provider_service_section_roles_id_rol_foreign` (`id_rol`),
  KEY `pss_section_idx` (`provider_service_section_id`),
  CONSTRAINT `provider_service_section_roles_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  CONSTRAINT `pss_role_section_fk` FOREIGN KEY (`provider_service_section_id`) REFERENCES `provider_services_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provider_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint unsigned NOT NULL,
  `key` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `available_credits` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_alert_client` decimal(10,2) NOT NULL DEFAULT '5.00',
  `min_alert_admin` decimal(10,2) NOT NULL DEFAULT '5.00',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_services_provider_id_key_unique` (`provider_id`,`key`),
  UNIQUE KEY `provider_services_service_code_unique` (`service_code`),
  CONSTRAINT `provider_services_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provider_services_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_services_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_service_id` bigint unsigned NOT NULL,
  `section_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pss_provider_service_section_unique` (`provider_service_id`,`section_code`),
  CONSTRAINT `provider_services_sections_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adapter_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `policies_json` json DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `providers_code_unique` (`code`),
  UNIQUE KEY `providers_adapter_code_unique` (`adapter_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `purchase_date` date NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `admin_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_provider_id_foreign` (`provider_id`),
  KEY `purchase_items_admin_id_foreign` (`admin_id`),
  KEY `purchase_items_provider_service_id_index` (`provider_service_id`),
  KEY `purchase_items_status_index` (`status`),
  CONSTRAINT `purchase_items_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_items_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_customer` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_types_type_name_unique` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_type_id` bigint unsigned DEFAULT NULL,
  `home_route` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `roles_nombre_unique` (`nombre`),
  KEY `roles_role_type_id_foreign` (`role_type_id`),
  CONSTRAINT `roles_role_type_id_foreign` FOREIGN KEY (`role_type_id`) REFERENCES `role_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `credit_package_id` bigint unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `assigned_by` bigint unsigned DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_packages_user_id_foreign` (`user_id`),
  KEY `user_packages_credit_package_id_foreign` (`credit_package_id`),
  KEY `user_packages_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `user_packages_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_packages_credit_package_id_foreign` FOREIGN KEY (`credit_package_id`) REFERENCES `credit_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_packages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_provider_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_provider_wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned DEFAULT NULL,
  `provider_service_id` bigint unsigned DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_alert` decimal(10,2) NOT NULL DEFAULT '5.00',
  `validity_start` timestamp NULL DEFAULT NULL,
  `validity_end` timestamp NULL DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `upw_user_service_unique` (`user_id`,`provider_service_id`),
  KEY `user_provider_wallets_provider_id_foreign` (`provider_id`),
  KEY `user_provider_wallets_provider_service_id_foreign` (`provider_service_id`),
  KEY `upw_user_id_index` (`user_id`),
  CONSTRAINT `user_provider_wallets_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_provider_wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_rol` bigint unsigned DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `es_oficial` tinyint(1) NOT NULL DEFAULT '0',
  `entidad` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `approved_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_otp` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_otp_expire` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_id_rol_foreign` (`id_rol`),
  CONSTRAINT `users_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned DEFAULT NULL,
  `criterio` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anio` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultimo_status_robo` tinyint(1) NOT NULL DEFAULT '0',
  `total_consultas` int unsigned NOT NULL DEFAULT '0',
  `ultima_consulta_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_provider_service_valor_unique` (`provider_service_id`,`valor`),
  KEY `vehicles_ultima_consulta_at_index` (`ultima_consulta_at`),
  CONSTRAINT `vehicles_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallet_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned DEFAULT NULL,
  `delta` decimal(10,2) NOT NULL,
  `reason` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `correlation_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallet_ledger_correlation_id_unique` (`correlation_id`),
  KEY `wallet_ledger_wallet_id_foreign` (`wallet_id`),
  KEY `wallet_ledger_provider_service_id_foreign` (`provider_service_id`),
  CONSTRAINT `wallet_ledger_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wallet_ledger_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `user_provider_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 DROP PROCEDURE IF EXISTS `add_column_unless_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_column_unless_exists`(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD COLUMN ', p_column, ' ', p_def);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_fk_cascade_unless_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_fk_cascade_unless_exists`(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT, IN p_ref_table VARCHAR(128), IN p_ref_cols TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = p_table AND constraint_name = p_name
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD CONSTRAINT ', p_name, ' FOREIGN KEY (', p_cols, ') REFERENCES ', p_ref_table, ' (', p_ref_cols, ') ON DELETE CASCADE');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_fk_unless_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_fk_unless_exists`(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT, IN p_ref_table VARCHAR(128), IN p_ref_cols TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = p_table AND constraint_name = p_name
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD CONSTRAINT ', p_name, ' FOREIGN KEY (', p_cols, ') REFERENCES ', p_ref_table, ' (', p_ref_cols, ') ON DELETE SET NULL');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_index_unless_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_index_unless_exists`(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_name
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD INDEX ', p_name, ' (', p_cols, ')');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_unique_unless_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_unique_unless_exists`(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_name
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD UNIQUE KEY ', p_name, ' (', p_cols, ')');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `drop_fk_if_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `drop_fk_if_exists`(IN p_table VARCHAR(128), IN p_name VARCHAR(128))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = p_table AND constraint_name = p_name
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' DROP FOREIGN KEY ', p_name);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `drop_index_if_exists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `drop_index_if_exists`(IN p_table VARCHAR(128), IN p_name VARCHAR(128))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_name
  ) THEN
    SET @sql = CONCAT('ALTER TABLE ', p_table, ' DROP INDEX ', p_name);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `_ps_migrate_consultations_step2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `_ps_migrate_consultations_step2`()
BEGIN
    DECLARE v_unassigned INT DEFAULT 0;
    DECLARE v_mismatched INT DEFAULT 0;
    DECLARE v_orphan INT DEFAULT 0;
    DECLARE v_idx_exists INT DEFAULT 0;
    DECLARE v_fk_exists INT DEFAULT 0;
    DECLARE v_is_nullable VARCHAR(3) DEFAULT 'YES';

    -- 1) Cero ambiguedades: ninguna consulta sin provider_service_id
    SELECT COUNT(*) INTO v_unassigned
    FROM `consultations`
    WHERE provider_service_id IS NULL;

    IF v_unassigned > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ABORTADO: existen consultas sin provider_service_id. Revisar step1 antes de continuar.';
    END IF;

    -- 2) Cero inconsistencias provider/provider_service
    SELECT COUNT(*) INTO v_mismatched
    FROM `consultations` c
    JOIN `provider_services` ps ON ps.id = c.provider_service_id
    WHERE c.provider_id <> ps.provider_id;

    IF v_mismatched > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ABORTADO: existen consultas con provider_service_id de un proveedor distinto.';
    END IF;

    -- 3) Cero referencias huerfanas (provider_service_id inexistente)
    SELECT COUNT(*) INTO v_orphan
    FROM `consultations` c
    LEFT JOIN `provider_services` ps ON ps.id = c.provider_service_id
    WHERE ps.id IS NULL;

    IF v_orphan > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ABORTADO: existen provider_service_id que no existen en provider_services.';
    END IF;

    -- Todas las validaciones pasaron: aplicar cambios estructurales

    -- 4) Indice compuesto (si no existe)
    SELECT COUNT(*) INTO v_idx_exists
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'consultations'
      AND index_name = 'consultations_service_criterio_valor_created_idx';

    IF v_idx_exists = 0 THEN
        ALTER TABLE `consultations`
            ADD INDEX `consultations_service_criterio_valor_created_idx`
            (`provider_service_id`, `criterio`, `valor`, `created_at`);
    END IF;

    -- 5) Foreign key (si no existe)
    SELECT COUNT(*) INTO v_fk_exists
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'consultations'
      AND constraint_name = 'consultations_provider_service_id_foreign'
      AND constraint_type = 'FOREIGN KEY';

    IF v_fk_exists = 0 THEN
        ALTER TABLE `consultations`
            ADD CONSTRAINT `consultations_provider_service_id_foreign`
            FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT;
    END IF;

    -- 6) NOT NULL final (solo si aun es nullable)
    SELECT is_nullable INTO v_is_nullable
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'consultations'
      AND column_name = 'provider_service_id';

    IF v_is_nullable = 'YES' THEN
        ALTER TABLE `consultations`
            MODIFY `provider_service_id` BIGINT UNSIGNED NOT NULL;
    END IF;

    SELECT 'OK: provider_service_id migrado, indexado, con FK y NOT NULL aplicados.' AS resultado;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_07_06_180000_create_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_07_06_180001_create_provider_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_07_06_180002_add_profile_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_07_06_180003_create_user_provider_wallets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_07_06_180004_create_wallet_ledger_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_07_07_120000_add_credit_cost_to_provider_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_07_07_120001_create_consultations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_07_08_000000_add_flags_json_to_consultations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_07_10_000001_create_credit_packages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_07_13_000000_update_users_roles_and_status',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_07_13_000001_create_admin_menu_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_07_14_000000_create_vehicles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_07_17_000000_create_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_07_17_000001_create_provider_services_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_07_17_000002_create_provider_service_section_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_07_17_000003_add_available_credits_to_provider_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_07_17_000004_restructure_provider_services_for_placas_service',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_07_17_000005_add_provider_service_id_to_user_provider_wallets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_07_17_000006_add_provider_service_id_to_wallet_ledger_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_07_17_000007_add_provider_service_id_to_credit_package_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_07_17_000008_add_provider_service_id_to_vehicles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_07_20_000000_create_inventory_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_07_21_000001_add_min_alert_columns_to_provider_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_07_21_190000_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_07_21_190001_add_id_rol_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_07_21_190002_add_id_rol_to_admin_menu_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_07_21_190003_add_id_rol_to_provider_service_section_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_07_21_200000_create_provider_service_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_07_23_033124_create_role_types_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_07_23_033142_add_role_type_id_and_meta_to_roles_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_07_23_101000_add_status_to_user_packages_and_wallets_tables',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_07_24_200000_create_global_configuration_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_07_27_131900_add_package_price_fields_to_global_configuration_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_07_27_210000_create_notification_policies_and_deliveries_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_07_28_200000_create_customer_menu_permissions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_07_28_230000_add_otp_fields_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_07_31_000001_add_adapter_code_to_providers_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_07_31_000002_add_service_code_to_provider_services_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_08_04_000001_add_vin_decoder_to_customer_menu_permissions',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_08_05_000001_create_menu_items_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_08_05_000002_drop_label_icon_from_menu_permissions_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_08_10_210000_add_provider_service_id_to_consultations_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_12_220000_add_notification_case_settings_to_global_configuration',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_12_220100_create_notification_case_guards_and_sequences',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_08_12_220200_create_notification_cases_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_08_12_220300_create_notification_case_support_tables',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_08_13_120000_add_notification_process_customer_menu',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_08_13_180000_add_notification_process_admin_menu',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_08_14_000000_update_vehicle_consultation_history_menus',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_08_14_120000_create_consultation_operations_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_08_14_130000_add_normalized_value_to_consultations',17);
