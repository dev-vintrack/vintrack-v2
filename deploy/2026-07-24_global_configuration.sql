-- Configuración global para compra de créditos
-- Ejecutar en PHPMyAdmin (no usar ON DUPLICATE KEY UPDATE)

CREATE TABLE IF NOT EXISTS `global_configuration` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `min_purchase_user` DECIMAL(10,2) NOT NULL DEFAULT 10,
    `max_purchase_user` DECIMAL(10,2) NOT NULL DEFAULT 1000,
    `step_purchase_input` DECIMAL(10,2) NOT NULL DEFAULT 10,
    `min_validity_days` INT UNSIGNED NOT NULL DEFAULT 30,
    `max_validity_days` INT UNSIGNED NOT NULL DEFAULT 360,
    `step_validity_input` INT UNSIGNED NOT NULL DEFAULT 30,
    `package_blocks_direct_credit` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `global_configuration` (
    `min_purchase_user`,
    `max_purchase_user`,
    `step_purchase_input`,
    `min_validity_days`,
    `max_validity_days`,
    `step_validity_input`,
    `package_blocks_direct_credit`,
    `created_at`,
    `updated_at`
)
SELECT
    10, 1000, 10, 30, 360, 30, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `global_configuration`);
