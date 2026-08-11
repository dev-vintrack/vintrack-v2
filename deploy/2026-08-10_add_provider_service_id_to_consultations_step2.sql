-- ==================================================================
-- Despliegue hosting: agregar provider_service_id a consultations
-- PASO 2 de 2: indice + foreign key + NOT NULL
-- Compatible con MariaDB/MySQL / phpMyAdmin (sin SSH/CLI)
-- Ejecutar SOLO despues de revisar manualmente las validaciones
-- del archivo "..._step1.sql" (deben regresar 0 filas / 0 unassigned).
--
-- Este script vuelve a validar automaticamente antes de modificar el
-- esquema: si detecta inconsistencias, ABORTA con un error explicito
-- y no aplica indice, FK ni NOT NULL.
-- ==================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `_ps_migrate_consultations_step2` $$
CREATE PROCEDURE `_ps_migrate_consultations_step2`()
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
END $$

DELIMITER ;

CALL `_ps_migrate_consultations_step2`();

DROP PROCEDURE IF EXISTS `_ps_migrate_consultations_step2`;

-- ==================================================================
-- VALIDACION FINAL (ejecutar y confirmar visualmente)
-- ==================================================================

SHOW CREATE TABLE `consultations`;

SELECT
    COUNT(*) AS total_consultations,
    COUNT(provider_service_id) AS consultations_with_service
FROM `consultations`;
