-- Normalización del catálogo de opciones de Menú (Admin y Cliente)
-- Objetivo: eliminar la duplicidad del nombre (label) de cada opción de menú,
-- que hoy se repite una vez por cada rol en admin_menu_permissions y
-- customer_menu_permissions. Ahora el nombre vive en una sola tabla catálogo
-- (menu_items) y se puede renombrar editando UNA sola fila desde phpMyAdmin,
-- sin afectar rutas ni permisos (que siguen dependiendo de route_name).
--
-- IMPORTANTE - ORDEN DE EJECUCIÓN:
--   PASO 1: ejecutar ANTES o junto con subir el código nuevo. Es seguro con
--           el código viejo porque solo agrega una tabla nueva sin tocarlo.
--   PASO 2: ejecutar DESPUÉS de subir el código nuevo y confirmar que el
--           menú de Admin y de Cliente cargan correctamente. Este paso
--           elimina las columnas label/icon duplicadas; si se ejecuta antes
--           de subir el código nuevo, el sitio viejo se rompe.

-- =========================================================
-- PASO 1: crear catálogo menu_items y poblarlo
-- =========================================================

CREATE TABLE IF NOT EXISTS menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(16) NOT NULL,
    route_name VARCHAR(128) NOT NULL,
    label VARCHAR(128) NOT NULL,
    icon VARCHAR(64) NULL,
    default_display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY menu_items_route_name_unique (route_name),
    KEY menu_items_scope_index (scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO menu_items (scope, route_name, label, icon, default_display_order, created_at, updated_at)
VALUES
    ('admin', 'home',                                    'Inicio',                     'house-door',       0,  NOW(), NOW()),
    ('admin', 'admin.consultations.index',                'Historial de Consultas',     'clipboard-data',   1,  NOW(), NOW()),
    ('admin', 'admin.inventory.index',                    'Inventario Global',          'boxes',            2,  NOW(), NOW()),
    ('admin', 'admin.purchases.index',                    'Compras',                    'cart',             3,  NOW(), NOW()),
    ('admin', 'admin.wallets.movements',                  'Movimientos Wallet',         'arrow-left-right', 4,  NOW(), NOW()),
    ('admin', 'admin.wallets.index',                      'Créditos por Usuario',       'wallet',           5,  NOW(), NOW()),
    ('admin', 'admin.vehicles.index',                     'Vehículos Registrados',      'car-front',        6,  NOW(), NOW()),
    ('admin', 'admin.packages.active',                    'Paquetes Activos',           'box-seam',         7,  NOW(), NOW()),
    ('admin', 'admin.credits.purchase',                   'Comprar Créditos',           'plus-circle',      8,  NOW(), NOW()),
    ('admin', 'admin.packages.index',                     'Paquetes',                   'boxes',            9,  NOW(), NOW()),
    ('admin', 'admin.providers.index',                    'Proveedores',                'hdd-network',      10, NOW(), NOW()),
    ('admin', 'admin.users.index',                        'Usuarios',                   'people',           11, NOW(), NOW()),
    ('admin', 'admin.menu-permissions.index',             'Permisos de Menú',           'sliders',          12, NOW(), NOW()),
    ('admin', 'admin.customer-menu-permissions.index',    'Permisos de Menú Cliente',   'sliders2',         13, NOW(), NOW()),
    ('admin', 'admin.provider-service-roles.index',       'Servicios por Rol',          'hand-thumbs-up',   14, NOW(), NOW()),
    ('admin', 'admin.roles.index',                        'Roles',                      'person-gear',      15, NOW(), NOW()),
    ('admin', 'admin.role-types.index',                   'Tipos de Rol',               'tags',             16, NOW(), NOW()),
    ('admin', 'admin.notifications.index',                'Notificaciones',             'envelope-check',   17, NOW(), NOW()),
    ('customer', 'customer.credits',                      'Mis créditos',               'wallet2',          0,  NOW(), NOW()),
    ('customer', 'customer.movements',                    'Mis movimientos',            'arrow-left-right', 1,  NOW(), NOW()),
    ('customer', 'customer.consultations',                'Mis consultas',              'clock-history',    2,  NOW(), NOW()),
    ('customer', 'customer.vin-decoder',                  'VIN Decoder',                'upc-scan',         3,  NOW(), NOW());

-- Defensivo: si en producción existiera alguna ruta con label distinto al
-- catálogo anterior (agregada manualmente antes de esta normalización),
-- incorporarla también para no perder ninguna opción de menú.
--
-- Nota MariaDB/MySQL: NO se puede usar "WHERE route_name NOT IN
-- (SELECT route_name FROM menu_items)" aquí porque no está permitido
-- seleccionar de la misma tabla en la que se inserta dentro de una
-- subconsulta (error: "You can't specify target table 'menu_items' for
-- update in FROM clause"). Por eso se omite el filtro NOT IN y se confía
-- en INSERT IGNORE + la restricción UNIQUE(route_name) para descartar
-- automáticamente los que ya existan en el catálogo.
INSERT IGNORE INTO menu_items (scope, route_name, label, icon, default_display_order, created_at, updated_at)
SELECT 'admin', route_name, label, icon, MIN(display_order), NOW(), NOW()
FROM admin_menu_permissions
GROUP BY route_name, label, icon;

INSERT IGNORE INTO menu_items (scope, route_name, label, icon, default_display_order, created_at, updated_at)
SELECT 'customer', route_name, label, icon, MIN(display_order), NOW(), NOW()
FROM customer_menu_permissions
GROUP BY route_name, label, icon;

-- =========================================================
-- PASO 2: ejecutar SOLO después de confirmar que el código nuevo
-- está desplegado y el menú funciona correctamente.
-- =========================================================

-- ALTER TABLE admin_menu_permissions DROP COLUMN label, DROP COLUMN icon;
-- ALTER TABLE customer_menu_permissions DROP COLUMN label, DROP COLUMN icon;
