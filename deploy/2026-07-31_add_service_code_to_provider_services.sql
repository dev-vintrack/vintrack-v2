-- ==================================================================
-- Despliegue hosting: agregar service_code a provider_services
-- Compatible con MariaDB 10.6 / phpMyAdmin (sin SSH/CLI)
-- Ejecutar en phpMyAdmin en la base de datos de la aplicacion
-- ==================================================================

-- 1. Agregar columna service_code si no existe
SET @addColumn = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'provider_services'
      AND column_name = 'service_code'
);

SET @sql = IF(@addColumn = 0,
    'ALTER TABLE `provider_services` ADD `service_code` VARCHAR(32) NULL AFTER `key`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Poblar service_code para los servicios conocidos (idempotente)
UPDATE `provider_services` ps
JOIN `providers` p ON p.id = ps.provider_id
SET ps.service_code = 'placas_service'
WHERE p.adapter_code = 'placas'
  AND ps.key = 'Placas_Service'
  AND ps.service_code IS NULL;

UPDATE `provider_services` ps
JOIN `providers` p ON p.id = ps.provider_id
SET ps.service_code = 'vhr'
WHERE p.adapter_code = 'vindata'
  AND ps.key = 'VHR'
  AND ps.service_code IS NULL;

UPDATE `provider_services` ps
JOIN `providers` p ON p.id = ps.provider_id
SET ps.service_code = 'nmvtis_plus'
WHERE p.adapter_code = 'vindata'
  AND ps.key = 'NMVTISPlus'
  AND ps.service_code IS NULL;

-- 3. Forzar NOT NULL en service_code si aun es nullable
SET @modifyColumn = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'provider_services'
      AND column_name = 'service_code'
      AND is_nullable = 'YES'
);

SET @sql2 = IF(@modifyColumn > 0,
    'ALTER TABLE `provider_services` MODIFY `service_code` VARCHAR(32) NOT NULL',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 4. Crear indice unico sobre service_code si no existe
SET @idxExists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'provider_services'
      AND index_name = 'provider_services_service_code_unique'
);

SET @sql3 = IF(@idxExists = 0,
    'ALTER TABLE `provider_services` ADD UNIQUE KEY `provider_services_service_code_unique` (`service_code`)',
    'SELECT 1'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
