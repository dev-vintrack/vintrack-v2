-- Migracion manual para hosting (PHPMyAdmin): agrega columna flags_json a consultations
-- Equivale a: 2026_07_08_000000_add_flags_json_to_consultations_table
-- Ejecutar en la pestana SQL de PHPMyAdmin sobre la BD del hosting.

-- 1) Agregar la columna flags_json despues de rapi_robo (solo si no existe)
--    Si al ejecutar marca error "Duplicate column name", significa que ya existe; ignorar.
ALTER TABLE consultations
    ADD COLUMN flags_json JSON NULL AFTER rapi_robo;

-- 2) Registrar la migracion en la tabla migrations para mantener consistencia con Laravel
INSERT INTO migrations (migration, batch)
SELECT '2026_07_08_000000_add_flags_json_to_consultations_table',
       (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)
WHERE NOT EXISTS (
    SELECT 1 FROM (
        SELECT migration FROM migrations
        WHERE migration = '2026_07_08_000000_add_flags_json_to_consultations_table'
    ) AS x
);
