-- Panel admin completo: tabla de permisos de menú y valores por defecto.
-- Compatible con PHPMyAdmin (sin ON DUPLICATE KEY UPDATE; usa INSERT IGNORE).
-- Ejecutar este script en PHPMyAdmin después de subir el código.

CREATE TABLE IF NOT EXISTS `admin_menu_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(32) NOT NULL,
  `route_name` varchar(128) NOT NULL,
  `label` varchar(128) NOT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_menu_permissions_role_route_name_unique` (`role`,`route_name`),
  KEY `admin_menu_permissions_role_enabled_display_order_index` (`role`,`enabled`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin: todas las opciones visibles
INSERT IGNORE INTO `admin_menu_permissions` (`role`, `route_name`, `label`, `icon`, `enabled`, `display_order`, `created_at`, `updated_at`) VALUES
('admin', 'home', 'Inicio', 'house-door', 1, 0, NOW(), NOW()),
('admin', 'admin.consultations.index', 'Historial de Consultas', 'clipboard-data', 1, 1, NOW(), NOW()),
('admin', 'admin.wallets.movements', 'Movimientos Wallet', 'arrow-left-right', 1, 2, NOW(), NOW()),
('admin', 'admin.wallets.index', 'Créditos por Usuario', 'wallet', 1, 3, NOW(), NOW()),
('admin', 'admin.vehicles.index', 'Vehículos Registrados', 'car-front', 1, 4, NOW(), NOW()),
('admin', 'admin.packages.active', 'Paquetes Activos', 'box-seam', 1, 5, NOW(), NOW()),
('admin', 'admin.credits.purchase', 'Comprar Créditos', 'plus-circle', 1, 6, NOW(), NOW()),
('admin', 'admin.packages.index', 'Paquetes', 'boxes', 1, 7, NOW(), NOW()),
('admin', 'admin.providers.index', 'Proveedores', 'hdd-network', 1, 8, NOW(), NOW()),
('admin', 'admin.users.index', 'Usuarios', 'people', 1, 9, NOW(), NOW()),
('admin', 'admin.menu-permissions.index', 'Permisos de Menú', 'sliders', 1, 10, NOW(), NOW());

-- Analista: solo operaciones originales (sin vehículos)
INSERT IGNORE INTO `admin_menu_permissions` (`role`, `route_name`, `label`, `icon`, `enabled`, `display_order`, `created_at`, `updated_at`) VALUES
('analista', 'home', 'Inicio', 'house-door', 0, 0, NOW(), NOW()),
('analista', 'admin.consultations.index', 'Historial de Consultas', 'clipboard-data', 0, 1, NOW(), NOW()),
('analista', 'admin.wallets.movements', 'Movimientos Wallet', 'arrow-left-right', 0, 2, NOW(), NOW()),
('analista', 'admin.wallets.index', 'Créditos por Usuario', 'wallet', 0, 3, NOW(), NOW()),
('analista', 'admin.vehicles.index', 'Vehículos Registrados', 'car-front', 0, 4, NOW(), NOW()),
('analista', 'admin.packages.active', 'Paquetes Activos', 'box-seam', 0, 5, NOW(), NOW()),
('analista', 'admin.credits.purchase', 'Comprar Créditos', 'plus-circle', 1, 6, NOW(), NOW()),
('analista', 'admin.packages.index', 'Paquetes', 'boxes', 1, 7, NOW(), NOW()),
('analista', 'admin.providers.index', 'Proveedores', 'hdd-network', 1, 8, NOW(), NOW()),
('analista', 'admin.users.index', 'Usuarios', 'people', 0, 9, NOW(), NOW()),
('analista', 'admin.menu-permissions.index', 'Permisos de Menú', 'sliders', 0, 10, NOW(), NOW());

-- Soporte: todo excepto la configuración de permisos y el home
INSERT IGNORE INTO `admin_menu_permissions` (`role`, `route_name`, `label`, `icon`, `enabled`, `display_order`, `created_at`, `updated_at`) VALUES
('soporte', 'home', 'Inicio', 'house-door', 0, 0, NOW(), NOW()),
('soporte', 'admin.consultations.index', 'Historial de Consultas', 'clipboard-data', 1, 1, NOW(), NOW()),
('soporte', 'admin.wallets.movements', 'Movimientos Wallet', 'arrow-left-right', 1, 2, NOW(), NOW()),
('soporte', 'admin.wallets.index', 'Créditos por Usuario', 'wallet', 1, 3, NOW(), NOW()),
('soporte', 'admin.vehicles.index', 'Vehículos Registrados', 'car-front', 1, 4, NOW(), NOW()),
('soporte', 'admin.packages.active', 'Paquetes Activos', 'box-seam', 1, 5, NOW(), NOW()),
('soporte', 'admin.credits.purchase', 'Comprar Créditos', 'plus-circle', 1, 6, NOW(), NOW()),
('soporte', 'admin.packages.index', 'Paquetes', 'boxes', 1, 7, NOW(), NOW()),
('soporte', 'admin.providers.index', 'Proveedores', 'hdd-network', 1, 8, NOW(), NOW()),
('soporte', 'admin.users.index', 'Usuarios', 'people', 1, 9, NOW(), NOW()),
('soporte', 'admin.menu-permissions.index', 'Permisos de Menú', 'sliders', 0, 10, NOW(), NOW());
