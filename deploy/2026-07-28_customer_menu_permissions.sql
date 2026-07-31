-- Customer menu permissions feature
-- Run this script through phpMyAdmin (cPanel) before or together with uploading the code.

CREATE TABLE IF NOT EXISTS customer_menu_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rol BIGINT UNSIGNED NOT NULL,
    route_name VARCHAR(128) NOT NULL,
    label VARCHAR(128) NOT NULL,
    icon VARCHAR(64) NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY customer_menu_permissions_id_rol_route_name_unique (id_rol, route_name),
    KEY customer_menu_permissions_id_rol_enabled_display_order_index (id_rol, enabled, display_order),
    CONSTRAINT customer_menu_permissions_id_rol_foreign FOREIGN KEY (id_rol) REFERENCES roles (id_rol) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default customer portal menu items for customer roles (excluding ocasional)
INSERT IGNORE INTO customer_menu_permissions (id_rol, route_name, label, icon, enabled, display_order, created_at, updated_at)
VALUES
    ((SELECT id_rol FROM roles WHERE nombre = 'cliente_registrado' LIMIT 1), 'customer.credits',       'Mis créditos',    'wallet2',          1, 0, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'cliente_registrado' LIMIT 1), 'customer.movements',     'Mis movimientos', 'arrow-left-right', 1, 1, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'cliente_registrado' LIMIT 1), 'customer.consultations', 'Mis consultas',   'clock-history',    1, 2, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'perito' LIMIT 1),             'customer.credits',       'Mis créditos',    'wallet2',          1, 0, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'perito' LIMIT 1),             'customer.movements',     'Mis movimientos', 'arrow-left-right', 1, 1, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'perito' LIMIT 1),             'customer.consultations', 'Mis consultas',   'clock-history',    1, 2, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'oficial' LIMIT 1),            'customer.credits',       'Mis créditos',    'wallet2',          1, 0, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'oficial' LIMIT 1),            'customer.movements',     'Mis movimientos', 'arrow-left-right', 1, 1, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'oficial' LIMIT 1),            'customer.consultations', 'Mis consultas',   'clock-history',    1, 2, NOW(), NOW());

-- Add the customer menu permissions screen to the admin menu for admin, soporte and analista.
INSERT IGNORE INTO admin_menu_permissions (id_rol, route_name, label, icon, enabled, display_order, created_at, updated_at)
VALUES
    ((SELECT id_rol FROM roles WHERE nombre = 'admin' LIMIT 1),     'admin.customer-menu-permissions.index', 'Permisos de Menú Cliente', 'sliders2', 1, 12, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'soporte' LIMIT 1),   'admin.customer-menu-permissions.index', 'Permisos de Menú Cliente', 'sliders2', 1, 12, NOW(), NOW()),
    ((SELECT id_rol FROM roles WHERE nombre = 'analista' LIMIT 1),  'admin.customer-menu-permissions.index', 'Permisos de Menú Cliente', 'sliders2', 1, 12, NOW(), NOW());
