-- ============================================================
-- Sprint 4: Sistema de Paquetes de Créditos
-- Ejecutar en PHPMyAdmin sobre la base de datos de VINTrack v2
-- ============================================================

CREATE TABLE IF NOT EXISTS `credit_packages` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100) NOT NULL,
    `description`   TEXT NULL,
    `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `validity_days` INT UNSIGNED NOT NULL DEFAULT 30,
    `active`        TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP NULL DEFAULT NULL,
    `updated_at`    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `credit_package_items` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `credit_package_id` BIGINT UNSIGNED NOT NULL,
    `provider_id`       BIGINT UNSIGNED NOT NULL,
    `credits`           DECIMAL(10,2) NOT NULL,
    `created_at`        TIMESTAMP NULL DEFAULT NULL,
    `updated_at`        TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cpi_package`  FOREIGN KEY (`credit_package_id`) REFERENCES `credit_packages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpi_provider` FOREIGN KEY (`provider_id`)       REFERENCES `providers`        (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_packages` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`           BIGINT UNSIGNED NOT NULL,
    `credit_package_id` BIGINT UNSIGNED NOT NULL,
    `assigned_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`        TIMESTAMP NULL DEFAULT NULL,
    `assigned_by`       BIGINT UNSIGNED NULL,
    `notes`             VARCHAR(255) NULL,
    `created_at`        TIMESTAMP NULL DEFAULT NULL,
    `updated_at`        TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_up_user`    FOREIGN KEY (`user_id`)           REFERENCES `users`           (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_package` FOREIGN KEY (`credit_package_id`) REFERENCES `credit_packages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_admin`   FOREIGN KEY (`assigned_by`)       REFERENCES `users`           (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
