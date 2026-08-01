-- ==================================================================
-- Despliegue hosting: agregar adapter_code a providers
-- Compatible con MariaDB 10.6 / phpMyAdmin (sin SSH/CLI)
-- Ejecutar en phpMyAdmin en la base de datos de la aplicacion
-- ==================================================================

-- 1. Agregar columna adapter_code si no existe
SET @addColumn = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'providers'
      AND column_name = 'adapter_code'
);

SET @sql = IF(@addColumn = 0,
    'ALTER TABLE `providers` ADD `adapter_code` VARCHAR(32) NULL AFTER `code`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Poblar adapter_code para los proveedores conocidos (idempotente)
UPDATE `providers` SET `adapter_code` = 'placas' WHERE `code` = 'PLACAS' AND `adapter_code` IS NULL;
UPDATE `providers` SET `adapter_code` = 'vindata' WHERE `code` = 'VINDATA' AND `adapter_code` IS NULL;

-- 3. Insertar proveedores base si no existen (usando adapter_code como clave estable)
INSERT IGNORE INTO `providers` (`code`, `adapter_code`, `name`, `base_url`, `policies_json`, `enabled`, `created_at`, `updated_at`)
VALUES (
    'PLACAS',
    'placas',
    'Placas.info',
    'https://placas.info/api/v2/consultar/',
    '{"debitTiming": "postAccept", "creditCost": 1.0, "resetPeriod": "none", "carryOver": true, "expireAfterDays": null}',
    1,
    NOW(),
    NOW()
);

INSERT IGNORE INTO `providers` (`code`, `adapter_code`, `name`, `base_url`, `policies_json`, `enabled`, `created_at`, `updated_at`)
VALUES (
    'VINDATA',
    'vindata',
    'VINData',
    'https://api.vindata.com/v1',
    '{"debitTiming": "postAccept", "creditCost": 1.0, "resetPeriod": "none", "carryOver": true, "expireAfterDays": null}',
    1,
    NOW(),
    NOW()
);

-- 4. Forzar NOT NULL en adapter_code si aun es nullable
SET @modifyColumn = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'providers'
      AND column_name = 'adapter_code'
      AND is_nullable = 'YES'
);

SET @sql2 = IF(@modifyColumn > 0,
    'ALTER TABLE `providers` MODIFY `adapter_code` VARCHAR(32) NOT NULL',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 5. Crear indice unico sobre adapter_code si no existe
SET @idxExists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'providers'
      AND index_name = 'providers_adapter_code_unique'
);

SET @sql3 = IF(@idxExists = 0,
    'ALTER TABLE `providers` ADD UNIQUE KEY `providers_adapter_code_unique` (`adapter_code`)',
    'SELECT 1'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
