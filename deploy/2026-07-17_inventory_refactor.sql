-- ============================================================
-- VINTrack v2 - Refactor de inventario y servicios
-- Fecha: 2026-07-17
-- Uso: Ejecutar en PHPMyAdmin sobre la base de datos existente.
-- Nota: Compatible con hosting compartido (sin ON DUPLICATE KEY UPDATE).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Helpers para DDL condicional
-- ============================================================
DROP PROCEDURE IF EXISTS add_column_unless_exists;
DELIMITER $$
CREATE PROCEDURE add_column_unless_exists(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_def TEXT)
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
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS add_index_unless_exists;
DELIMITER $$
CREATE PROCEDURE add_index_unless_exists(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT)
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
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS add_unique_unless_exists;
DELIMITER $$
CREATE PROCEDURE add_unique_unless_exists(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT)
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
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS drop_index_if_exists;
DELIMITER $$
CREATE PROCEDURE drop_index_if_exists(IN p_table VARCHAR(128), IN p_name VARCHAR(128))
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
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS add_fk_unless_exists;
DELIMITER $$
CREATE PROCEDURE add_fk_unless_exists(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT, IN p_ref_table VARCHAR(128), IN p_ref_cols TEXT)
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
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS add_fk_cascade_unless_exists;
DELIMITER $$
CREATE PROCEDURE add_fk_cascade_unless_exists(IN p_table VARCHAR(128), IN p_name VARCHAR(128), IN p_cols TEXT, IN p_ref_table VARCHAR(128), IN p_ref_cols TEXT)
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
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS drop_fk_if_exists;
DELIMITER $$
CREATE PROCEDURE drop_fk_if_exists(IN p_table VARCHAR(128), IN p_name VARCHAR(128))
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
END$$
DELIMITER ;

-- ============================================================
-- 1. Tablas nuevas
-- ============================================================

CREATE TABLE IF NOT EXISTS `purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `purchase_date` date NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `notes` text NULL,
  `admin_id` bigint unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pi_provider_id_index` (`provider_id`),
  KEY `pi_provider_service_id_index` (`provider_service_id`),
  KEY `pi_admin_id_index` (`admin_id`),
  KEY `pi_purchase_date_index` (`purchase_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL add_fk_cascade_unless_exists('purchase_items', 'purchase_items_provider_id_foreign', 'provider_id', 'providers', 'id');
CALL add_fk_cascade_unless_exists('purchase_items', 'purchase_items_provider_service_id_foreign', 'provider_service_id', 'provider_services', 'id');

DROP PROCEDURE IF EXISTS add_purchase_items_admin_fk;
DELIMITER $$
CREATE PROCEDURE add_purchase_items_admin_fk()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'purchase_items' AND constraint_name = 'purchase_items_admin_id_foreign'
  ) THEN
    ALTER TABLE `purchase_items` ADD CONSTRAINT `purchase_items_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
  END IF;
END$$
DELIMITER ;
CALL add_purchase_items_admin_fk();
DROP PROCEDURE IF EXISTS add_purchase_items_admin_fk;

CREATE TABLE IF NOT EXISTS `provider_services_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_service_id` bigint unsigned NOT NULL,
  `section_code` varchar(32) NOT NULL,
  `section_name` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pss_provider_service_section_unique` (`provider_service_id`,`section_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_pss_fk;
DELIMITER $$
CREATE PROCEDURE add_pss_fk()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'provider_services_sections' AND constraint_name = 'pss_provider_service_fk'
  ) THEN
    ALTER TABLE `provider_services_sections` ADD CONSTRAINT `pss_provider_service_fk` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE;
  END IF;
END$$
DELIMITER ;
CALL add_pss_fk();
DROP PROCEDURE IF EXISTS add_pss_fk;

CREATE TABLE IF NOT EXISTS `provider_service_section_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_service_section_id` bigint unsigned NOT NULL,
  `role` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pss_role_unique` (`provider_service_section_id`,`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_pssr_fk;
DELIMITER $$
CREATE PROCEDURE add_pssr_fk()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'provider_service_section_roles' AND constraint_name = 'pss_role_section_fk'
  ) THEN
    ALTER TABLE `provider_service_section_roles` ADD CONSTRAINT `pss_role_section_fk` FOREIGN KEY (`provider_service_section_id`) REFERENCES `provider_services_sections` (`id`) ON DELETE CASCADE;
  END IF;
END$$
DELIMITER ;
CALL add_pssr_fk();
DROP PROCEDURE IF EXISTS add_pssr_fk;

-- ============================================================
-- 2. Columna available_credits en provider_services
-- ============================================================
CALL add_column_unless_exists('provider_services', 'available_credits', 'decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `credit_cost`');

-- ============================================================
-- 3. Crear servicio consolidado Placas_Service y secciones/roles
-- ============================================================

SET @placas_provider_id = (SELECT id FROM providers WHERE code = 'PLACAS' LIMIT 1);

INSERT INTO `provider_services` (`provider_id`, `key`, `name`, `credit_cost`, `available_credits`, `enabled`, `created_at`, `updated_at`)
SELECT @placas_provider_id, 'Placas_Service', 'Placas Service', 0, 0, 1, NOW(), NOW()
FROM DUAL
WHERE @placas_provider_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM provider_services WHERE provider_id = @placas_provider_id AND `key` = 'Placas_Service');

SET @placas_service_id = (SELECT id FROM provider_services WHERE provider_id = @placas_provider_id AND `key` = 'Placas_Service' LIMIT 1);

INSERT INTO `provider_services_sections` (`provider_service_id`, `section_code`, `section_name`, `status`, `created_at`, `updated_at`)
SELECT @placas_service_id, code, name, 1, NOW(), NOW()
FROM (
  SELECT 'repuve' AS code, 'REPUVE' AS name UNION ALL
  SELECT 'pgj', 'PGJ' UNION ALL
  SELECT 'aviso', 'Aviso Judicial' UNION ALL
  SELECT 'ocra', 'OCRA' UNION ALL
  SELECT 'carfax', 'CARFAX' UNION ALL
  SELECT 'rapi', 'RAPI'
) AS sections
WHERE @placas_service_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM provider_services_sections pss
    WHERE pss.provider_service_id = @placas_service_id AND pss.section_code = sections.code
  );

INSERT INTO `provider_service_section_roles` (`provider_service_section_id`, `role`, `status`, `created_at`, `updated_at`)
SELECT pss.id, defaults.role, defaults.status, NOW(), NOW()
FROM provider_services_sections pss
CROSS JOIN (
  SELECT 'admin' AS role, 1 AS status UNION ALL
  SELECT 'analista', 1 UNION ALL
  SELECT 'soporte', 1 UNION ALL
  SELECT 'cliente_registrado', 1 UNION ALL
  SELECT 'perito', 1 UNION ALL
  SELECT 'oficial', 1 UNION ALL
  SELECT 'ocasional', 1
) AS defaults
WHERE pss.provider_service_id = @placas_service_id
  AND NOT EXISTS (
    SELECT 1 FROM provider_service_section_roles pssr
    WHERE pssr.provider_service_section_id = pss.id AND pssr.role = defaults.role
  );

-- Desactivar RAPI para analista, soporte y cliente_registrado
UPDATE `provider_service_section_roles` pssr
JOIN `provider_services_sections` pss ON pss.id = pssr.provider_service_section_id
SET pssr.status = 0
WHERE pss.provider_service_id = @placas_service_id
  AND pss.section_code = 'rapi'
  AND pssr.role IN ('analista', 'soporte', 'cliente_registrado');

-- ============================================================
-- 4. Eliminar antiguos servicios individuales de Placas
-- ============================================================
DELETE FROM `provider_services`
WHERE provider_id = @placas_provider_id
  AND `key` != 'Placas_Service';

-- ============================================================
-- 5. provider_service_id en user_provider_wallets
-- ============================================================
CALL add_column_unless_exists('user_provider_wallets', 'provider_service_id', 'bigint unsigned NULL AFTER `provider_id`');

SET @vindata_provider_id = (SELECT id FROM providers WHERE code = 'VINDATA' LIMIT 1);
SET @nmvtis_service_id = (SELECT id FROM provider_services WHERE provider_id = @vindata_provider_id AND `key` = 'NMVTISPlus' LIMIT 1);

UPDATE `user_provider_wallets`
SET provider_service_id = @placas_service_id
WHERE provider_id = @placas_provider_id;

UPDATE `user_provider_wallets`
SET provider_service_id = @nmvtis_service_id
WHERE provider_id = @vindata_provider_id;

-- Eliminar foreign key sobre provider_id si existe
DROP PROCEDURE IF EXISTS drop_provider_id_fk_if_exists;
DELIMITER $$
CREATE PROCEDURE drop_provider_id_fk_if_exists()
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'user_provider_wallets'
      AND constraint_type = 'FOREIGN KEY'
      AND constraint_name LIKE '%provider_id_foreign%'
  ) THEN
    SET @drop_sql = (SELECT CONCAT('ALTER TABLE user_provider_wallets DROP FOREIGN KEY ', constraint_name)
                     FROM information_schema.table_constraints
                     WHERE constraint_schema = DATABASE()
                       AND table_name = 'user_provider_wallets'
                       AND constraint_type = 'FOREIGN KEY'
                       AND constraint_name LIKE '%provider_id_foreign%');
    PREPARE stmt FROM @drop_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;
CALL drop_provider_id_fk_if_exists();
DROP PROCEDURE IF EXISTS drop_provider_id_fk_if_exists;

CALL drop_index_if_exists('user_provider_wallets', 'user_provider_wallets_user_id_provider_id_unique');
CALL add_unique_unless_exists('user_provider_wallets', 'upw_user_service_unique', 'user_id, provider_service_id');
CALL add_index_unless_exists('user_provider_wallets', 'upw_user_id_index', 'user_id');

ALTER TABLE `user_provider_wallets` MODIFY COLUMN `provider_id` bigint unsigned NULL;

CALL add_fk_unless_exists('user_provider_wallets', 'user_provider_wallets_provider_service_id_foreign', 'provider_service_id', 'provider_services', 'id');

-- ============================================================
-- 6. provider_service_id en wallet_ledger
-- ============================================================
CALL add_column_unless_exists('wallet_ledger', 'provider_service_id', 'bigint unsigned NULL AFTER `wallet_id`');

UPDATE `wallet_ledger` wl
JOIN `user_provider_wallets` w ON w.id = wl.wallet_id
SET wl.provider_service_id = w.provider_service_id
WHERE w.provider_service_id IS NOT NULL;

CALL add_fk_unless_exists('wallet_ledger', 'wallet_ledger_provider_service_id_foreign', 'provider_service_id', 'provider_services', 'id');

-- ============================================================
-- 7. provider_service_id en credit_package_items
-- ============================================================
CALL add_column_unless_exists('credit_package_items', 'provider_service_id', 'bigint unsigned NULL AFTER `provider_id`');

UPDATE `credit_package_items`
SET provider_service_id = @placas_service_id
WHERE provider_id = @placas_provider_id;

UPDATE `credit_package_items`
SET provider_service_id = @nmvtis_service_id
WHERE provider_id = @vindata_provider_id;

CALL add_fk_unless_exists('credit_package_items', 'credit_package_items_provider_service_id_foreign', 'provider_service_id', 'provider_services', 'id');

-- ============================================================
-- 8. provider_service_id en vehicles
-- ============================================================
CALL add_column_unless_exists('vehicles', 'provider_service_id', 'bigint unsigned NULL AFTER `provider_id`');

UPDATE `vehicles`
SET provider_service_id = @placas_service_id
WHERE provider_id = @placas_provider_id;

UPDATE `vehicles`
SET provider_service_id = @nmvtis_service_id
WHERE provider_id = @vindata_provider_id;

UPDATE `vehicles` v
SET v.provider_service_id = (
    SELECT id FROM provider_services
    WHERE provider_services.provider_id = v.provider_id
    LIMIT 1
)
WHERE v.provider_service_id IS NULL;

CALL drop_index_if_exists('vehicles', 'vehicles_provider_valor_unique');
CALL add_unique_unless_exists('vehicles', 'vehicles_provider_service_valor_unique', 'provider_service_id, valor');
CALL add_fk_unless_exists('vehicles', 'vehicles_provider_service_id_foreign', 'provider_service_id', 'provider_services', 'id');

SET FOREIGN_KEY_CHECKS = 1;
