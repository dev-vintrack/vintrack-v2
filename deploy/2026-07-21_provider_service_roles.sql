-- Despliegue hosting: tabla provider_service_roles y permisos de menú relacionados
-- Compatible con PHPMyAdmin (sin ON DUPLICATE KEY UPDATE)

CREATE TABLE IF NOT EXISTS `provider_service_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` bigint unsigned NOT NULL,
  `provider_service_id` bigint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `psr_role_service_unique` (`id_rol`,`provider_service_id`),
  KEY `provider_service_roles_id_rol_foreign` (`id_rol`),
  KEY `provider_service_roles_provider_service_id_foreign` (`provider_service_id`),
  CONSTRAINT `provider_service_roles_id_rol_foreign` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  CONSTRAINT `provider_service_roles_provider_service_id_foreign` FOREIGN KEY (`provider_service_id`) REFERENCES `provider_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poblar con todas las combinaciones existentes (activas)
INSERT IGNORE INTO `provider_service_roles` (`id_rol`, `provider_service_id`, `status`, `created_at`, `updated_at`)
SELECT r.id_rol, ps.id, 1, NOW(), NOW()
FROM `roles` r
CROSS JOIN `provider_services` ps
WHERE ps.enabled = 1;

-- Nuevas opciones de menú para los CRUDs creados (solo admin habilitado por defecto)
INSERT IGNORE INTO `admin_menu_permissions` (`id_rol`, `route_name`, `label`, `icon`, `enabled`, `display_order`)
SELECT r.id_rol, 'admin.provider-service-roles.index', 'Servicios por Rol', 'hand-thumbs-up', IF(r.nombre = 'admin', 1, 0), 99
FROM `roles` r;

INSERT IGNORE INTO `admin_menu_permissions` (`id_rol`, `route_name`, `label`, `icon`, `enabled`, `display_order`)
SELECT r.id_rol, 'admin.roles.index', 'Roles', 'person-gear', IF(r.nombre = 'admin', 1, 0), 99
FROM `roles` r;
