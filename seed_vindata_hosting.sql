-- Seed VINDATA provider and services for hosting deployment (PHPMyAdmin compatible)
-- NO usa ON DUPLICATE KEY UPDATE. Es idempotente: solo inserta si no existe.
-- Ejecutar en la pestana SQL de PHPMyAdmin sobre la BD del hosting.

-- 1) Insertar proveedor VINDATA solo si no existe (no afecta llaves foraneas)
INSERT INTO providers (code, name, base_url, policies_json, enabled, created_at, updated_at)
SELECT
    'VINDATA',
    'VINData',
    'https://api.vindata.com/v1',
    '{"debitTiming":"postAccept","creditCost":1,"resetPeriod":"none","carryOver":true,"expireAfterDays":null}',
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM providers WHERE code = 'VINDATA');

-- 2) Obtener el id del proveedor VINDATA
SELECT @vindata_id := id FROM providers WHERE code = 'VINDATA' LIMIT 1;

-- 3) Insertar servicio VHR solo si no existe
INSERT INTO provider_services (provider_id, `key`, name, credit_cost, enabled, created_at, updated_at)
SELECT @vindata_id, 'VHR', 'VIN History Report', 1, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM provider_services WHERE provider_id = @vindata_id AND `key` = 'VHR'
);

-- 4) Insertar servicio NMVTISPlus solo si no existe
INSERT INTO provider_services (provider_id, `key`, name, credit_cost, enabled, created_at, updated_at)
SELECT @vindata_id, 'NMVTISPlus', 'NMVTIS+', 1, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM provider_services WHERE provider_id = @vindata_id AND `key` = 'NMVTISPlus'
);
