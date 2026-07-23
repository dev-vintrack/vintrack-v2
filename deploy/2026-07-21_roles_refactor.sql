-- ============================================================
-- Roles refactor: tabla roles + id_rol en users,
-- admin_menu_permissions y provider_service_section_roles
-- Ejecutar en PHPMyAdmin en la BD de producción
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Tabla roles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `roles_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `roles` (`nombre`, `descripcion`, `status`, `display_order`, `fecha_creacion`, `updated_at`) VALUES
('admin', 'Administrador del sistema', 1, 1, NOW(), NOW()),
('analista', 'Analista operativo', 1, 2, NOW(), NOW()),
('soporte', 'Soporte técnico', 1, 3, NOW(), NOW()),
('cliente_registrado', 'Cliente registrado', 1, 4, NOW(), NOW()),
('perito', 'Cliente perito', 1, 5, NOW(), NOW()),
('oficial', 'Cliente con cargo oficial', 1, 6, NOW(), NOW()),
('ocasional', 'Cliente ocasional', 1, 7, NOW(), NOW()),
('nuevo_rol', 'Nuevo rol por definir', 1, 8, NOW(), NOW());

-- ------------------------------------------------------------
-- 2. users: reemplazar rol por id_rol
-- ------------------------------------------------------------
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `id_rol` bigint unsigned NULL AFTER `telefono`;

UPDATE `users`
SET `id_rol` = COALESCE(
    (SELECT `id_rol` FROM `roles` WHERE `roles`.`nombre` = `users`.`rol` LIMIT 1),
    (SELECT `id_rol` FROM `roles` WHERE `roles`.`nombre` = 'cliente_registrado' LIMIT 1)
);

ALTER TABLE `users` DROP COLUMN IF EXISTS `rol`;

ALTER TABLE `users`
  ADD CONSTRAINT `users_id_rol_foreign`
  FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- 3. admin_menu_permissions: reemplazar role por id_rol
-- ------------------------------------------------------------
ALTER TABLE `admin_menu_permissions` ADD COLUMN IF NOT EXISTS `id_rol` bigint unsigned NULL AFTER `id`;

UPDATE `admin_menu_permissions`
SET `id_rol` = (SELECT `id_rol` FROM `roles` WHERE `roles`.`nombre` = `admin_menu_permissions`.`role` LIMIT 1);

ALTER TABLE `admin_menu_permissions`
  DROP INDEX IF EXISTS `admin_menu_permissions_role_route_name_unique`,
  DROP INDEX IF EXISTS `admin_menu_permissions_role_enabled_display_order_index`;

ALTER TABLE `admin_menu_permissions` DROP COLUMN IF EXISTS `role`;

ALTER TABLE `admin_menu_permissions`
  ADD UNIQUE KEY `admin_menu_permissions_id_rol_route_name_unique` (`id_rol`, `route_name`),
  ADD KEY `admin_menu_permissions_id_rol_enabled_display_order_index` (`id_rol`, `enabled`, `display_order`),
  ADD CONSTRAINT `admin_menu_permissions_id_rol_foreign`
  FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE;

-- ------------------------------------------------------------
-- 4. provider_service_section_roles: reemplazar role por id_rol
-- ------------------------------------------------------------
ALTER TABLE `provider_service_section_roles` ADD COLUMN IF NOT EXISTS `id_rol` bigint unsigned NULL AFTER `provider_service_section_id`;

ALTER TABLE `provider_service_section_roles`
  ADD KEY IF NOT EXISTS `pss_section_idx` (`provider_service_section_id`);

UPDATE `provider_service_section_roles`
SET `id_rol` = (SELECT `id_rol` FROM `roles` WHERE `roles`.`nombre` = `provider_service_section_roles`.`role` LIMIT 1);

ALTER TABLE `provider_service_section_roles`
  DROP INDEX IF EXISTS `pss_role_unique`;

ALTER TABLE `provider_service_section_roles` DROP COLUMN IF EXISTS `role`;

ALTER TABLE `provider_service_section_roles`
  ADD UNIQUE KEY `pss_id_rol_unique` (`provider_service_section_id`, `id_rol`),
  ADD CONSTRAINT `provider_service_section_roles_id_rol_foreign`
  FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
