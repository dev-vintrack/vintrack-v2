CREATE TABLE IF NOT EXISTS `notification_policies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider_service_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(64) NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `low_balance_threshold` DECIMAL(10,2) NULL,
    `expiring_days` JSON NULL,
    `cooldown_hours` INT UNSIGNED NOT NULL DEFAULT 72,
    `bcc_email` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `notification_policy_service_event_unique` (`provider_service_id`, `event_type`),
    KEY `notification_policies_event_type_enabled_index` (`event_type`, `enabled`),
    CONSTRAINT `notification_policies_provider_service_id_foreign`
        FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `provider_service_id` BIGINT UNSIGNED NULL,
    `event_type` VARCHAR(64) NOT NULL,
    `related_type` VARCHAR(128) NULL,
    `related_id` BIGINT UNSIGNED NULL,
    `recipient` VARCHAR(255) NULL,
    `bcc` VARCHAR(255) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` VARCHAR(24) NOT NULL DEFAULT 'pending',
    `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `dedup_key` VARCHAR(191) NOT NULL,
    `scheduled_for` TIMESTAMP NULL,
    `sent_at` TIMESTAMP NULL,
    `failed_at` TIMESTAMP NULL,
    `error` TEXT NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `notification_deliveries_uuid_unique` (`uuid`),
    UNIQUE KEY `notification_deliveries_dedup_key_unique` (`dedup_key`),
    KEY `notification_deliveries_event_type_status_index` (`event_type`, `status`),
    KEY `notification_deliveries_service_created_index` (`provider_service_id`, `created_at`),
    KEY `notification_deliveries_user_created_index` (`user_id`, `created_at`),
    CONSTRAINT `notification_deliveries_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `notification_deliveries_provider_service_id_foreign`
        FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notification_policies`
    (`provider_service_id`, `event_type`, `enabled`, `low_balance_threshold`, `expiring_days`, `cooldown_hours`, `bcc_email`, `created_at`, `updated_at`)
SELECT `id`, 'wallet.low_balance', 1, `min_alert_client`, NULL, 72, NULL, NOW(), NOW()
FROM `provider_services`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `notification_policies`
    (`provider_service_id`, `event_type`, `enabled`, `low_balance_threshold`, `expiring_days`, `cooldown_hours`, `bcc_email`, `created_at`, `updated_at`)
SELECT `id`, 'wallet.zero_balance', 1, NULL, NULL, 0, NULL, NOW(), NOW()
FROM `provider_services`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `notification_policies`
    (`provider_service_id`, `event_type`, `enabled`, `low_balance_threshold`, `expiring_days`, `cooldown_hours`, `bcc_email`, `created_at`, `updated_at`)
SELECT `id`, 'wallet.expiring', 1, NULL, JSON_ARRAY(7, 3, 1), 0, NULL, NOW(), NOW()
FROM `provider_services`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `notification_policies`
    (`provider_service_id`, `event_type`, `enabled`, `low_balance_threshold`, `expiring_days`, `cooldown_hours`, `bcc_email`, `created_at`, `updated_at`)
SELECT `id`, 'wallet.expired', 1, NULL, NULL, 0, NULL, NOW(), NOW()
FROM `provider_services`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `notification_policies`
    (`provider_service_id`, `event_type`, `enabled`, `low_balance_threshold`, `expiring_days`, `cooldown_hours`, `bcc_email`, `created_at`, `updated_at`)
SELECT `id`, 'consultation.risk_alert', 1, NULL, NULL, 0, NULL, NOW(), NOW()
FROM `provider_services`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `admin_menu_permissions`
    (`id_rol`, `route_name`, `label`, `icon`, `enabled`, `display_order`, `created_at`, `updated_at`)
SELECT `id_rol`, 'admin.notifications.index', 'Notificaciones', 'envelope-check', 1, 16, NOW(), NOW()
FROM `roles`
WHERE `nombre` = 'admin'
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`),
    `icon` = VALUES(`icon`),
    `enabled` = VALUES(`enabled`),
    `display_order` = VALUES(`display_order`),
    `updated_at` = VALUES(`updated_at`);
