-- Script para actualizar el esquema y datos de role_types en PHPMyAdmin
-- NOTA: Ejecutar despues de que el esquema base de roles este aplicado.

-- 1. Crear tabla role_types
CREATE TABLE IF NOT EXISTS `role_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `type_name` VARCHAR(32) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `color` VARCHAR(32) NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `is_customer` TINYINT(1) NOT NULL DEFAULT 0,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `role_types_status_display_order_index` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Agregar columnas a roles
ALTER TABLE `roles`
    ADD COLUMN IF NOT EXISTS `role_type_id` BIGINT UNSIGNED NULL AFTER `descripcion`,
    ADD COLUMN IF NOT EXISTS `home_route` VARCHAR(64) NULL AFTER `role_type_id`,
    ADD COLUMN IF NOT EXISTS `requires_approval` TINYINT(1) NOT NULL DEFAULT 0 AFTER `home_route`,
    ADD CONSTRAINT `roles_role_type_id_foreign`
        FOREIGN KEY (`role_type_id`) REFERENCES `role_types` (`id`)
        ON DELETE SET NULL;

-- 3. Poblar tipos de rol por defecto
INSERT IGNORE INTO `role_types` (`type_name`, `description`, `color`, `is_admin`, `is_customer`, `status`, `display_order`, `created_at`, `updated_at`) VALUES
    ('Administrativo', 'Roles con acceso al panel administrativo', 'danger', 1, 0, 1, 1, NOW(), NOW()),
    ('Cliente', 'Roles de clientes finales', 'info', 0, 1, 1, 2, NOW(), NOW());

-- 4. Asignar role_type_id a roles existentes
UPDATE `roles` SET
    `role_type_id` = (SELECT `id` FROM `role_types` WHERE `type_name` = 'Administrativo' LIMIT 1),
    `home_route` = 'home',
    `requires_approval` = 0
WHERE `nombre` IN ('admin', 'analista', 'soporte');

UPDATE `roles` SET
    `role_type_id` = (SELECT `id` FROM `role_types` WHERE `type_name` = 'Cliente' LIMIT 1),
    `home_route` = 'home.cliente',
    `requires_approval` = 0
WHERE `nombre` = 'cliente_registrado';

UPDATE `roles` SET
    `role_type_id` = (SELECT `id` FROM `role_types` WHERE `type_name` = 'Cliente' LIMIT 1),
    `home_route` = 'home.perito',
    `requires_approval` = 1
WHERE `nombre` = 'perito';

UPDATE `roles` SET
    `role_type_id` = (SELECT `id` FROM `role_types` WHERE `type_name` = 'Cliente' LIMIT 1),
    `home_route` = 'home.oficial',
    `requires_approval` = 1
WHERE `nombre` = 'oficial';

UPDATE `roles` SET
    `role_type_id` = (SELECT `id` FROM `role_types` WHERE `type_name` = 'Cliente' LIMIT 1),
    `home_route` = 'home.ocasional',
    `requires_approval` = 0
WHERE `nombre` = 'ocasional';

UPDATE `roles` SET
    `role_type_id` = (SELECT `id` FROM `role_types` WHERE `type_name` = 'Cliente' LIMIT 1),
    `home_route` = NULL,
    `requires_approval` = 0
WHERE `nombre` = 'nuevo_rol';

-- 5. Agregar permiso de menu para Tipos de Rol (solo admin)
INSERT IGNORE INTO `admin_menu_permissions` (`id_rol`, `route_name`, `label`, `icon`, `enabled`, `display_order`, `created_at`, `updated_at`)
SELECT `id_rol`, 'admin.role-types.index', 'Tipos de Rol', 'tags', 1, 99, NOW(), NOW()
FROM `roles`
WHERE `nombre` = 'admin';
