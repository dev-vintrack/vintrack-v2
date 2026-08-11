-- ==================================================================
-- Despliegue hosting: agregar provider_service_id a consultations
-- PASO 1 de 2: columna (NULL) + migracion de historico + validacion
-- Compatible con MariaDB/MySQL / phpMyAdmin (sin SSH/CLI)
-- Ejecutar en phpMyAdmin en la base de datos de la aplicacion
--
-- IMPORTANTE:
--   Antes de ejecutar, realizar backup de las tablas:
--     consultations, provider_services, providers
--   (Exportar -> SQL, en phpMyAdmin)
--
--   Al finalizar este script se muestran 3 consultas de validacion.
--   NO ejecutar el archivo "..._step2.sql" a menos que las 3
--   consultas de validacion regresen 0 filas / 0 en los conteos.
--   Si aparece algun registro, reportar: id, provider_id, criterio,
--   valor, services y detener el proceso (no adivinar el mapeo).
-- ==================================================================

-- 1. Agregar columna provider_service_id (NULL) si no existe
SET @addColumn = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'consultations'
      AND column_name = 'provider_service_id'
);

SET @sql = IF(@addColumn = 0,
    'ALTER TABLE `consultations` ADD `provider_service_id` BIGINT UNSIGNED NULL AFTER `provider_id`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Verificar tipo/existencia real de la columna antes de continuar
SELECT column_name, data_type, column_type, is_nullable
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'consultations'
  AND column_name = 'provider_service_id';

-- 3. Migrar historico
-- Caso A: providers con un unico provider_service -> asignacion directa
--         (relacion deterministica: no hay ambiguedad posible)
UPDATE `consultations` c
JOIN (
    SELECT provider_id, MIN(id) AS only_service_id
    FROM `provider_services`
    GROUP BY provider_id
    HAVING COUNT(*) = 1
) ps ON ps.provider_id = c.provider_id
SET c.provider_service_id = ps.only_service_id
WHERE c.provider_service_id IS NULL;

-- Caso B: providers con multiples provider_services -> desambiguar
--         usando el primer valor de `services` (case-insensitive)
--         contra `key` o `service_code` de provider_services
UPDATE `consultations` c
JOIN `provider_services` ps
    ON ps.provider_id = c.provider_id
    AND (
        LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.services, '$[0]'))) = LOWER(ps.`key`)
        OR LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.services, '$[0]'))) = LOWER(ps.service_code)
    )
SET c.provider_service_id = ps.id
WHERE c.provider_service_id IS NULL;

-- ==================================================================
-- VALIDACIONES OBLIGATORIAS (revisar manualmente antes de step 2)
-- ==================================================================

-- Validacion 1: consultas sin provider_service_id asignado (debe ser 0 filas)
SELECT
    id,
    user_id,
    provider_id,
    criterio,
    valor,
    services,
    'AMBIGUO O SIN PROVIDER_SERVICE CORRESPONDIENTE' AS motivo
FROM `consultations`
WHERE provider_service_id IS NULL;

-- Validacion 2: conteo total vs asignados (deben coincidir)
SELECT
    COUNT(*) AS total,
    COUNT(provider_service_id) AS assigned,
    COUNT(*) - COUNT(provider_service_id) AS unassigned
FROM `consultations`;

-- Validacion 3: provider_service_id asignado pero de un proveedor distinto (debe ser 0 filas)
SELECT
    c.id,
    c.provider_id,
    c.provider_service_id,
    ps.provider_id AS service_provider_id
FROM `consultations` c
JOIN `provider_services` ps ON ps.id = c.provider_service_id
WHERE c.provider_id <> ps.provider_id;

-- Validacion 4: conteo de provider_id vs provider_service_id resultante (para el reporte tecnico)
SELECT
    c.provider_id,
    c.provider_service_id,
    COUNT(*) AS registros
FROM `consultations` c
GROUP BY c.provider_id, c.provider_service_id
ORDER BY c.provider_id, c.provider_service_id;
