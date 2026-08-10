-- ============================================================
-- Nuevo Rol: unidad_analisis (copia de oficial)
-- Compatible con MariaDB 10.6 / PHPMyAdmin
-- ============================================================

SET @oficial_id = (SELECT id_rol FROM roles WHERE nombre = 'oficial' LIMIT 1);

INSERT IGNORE INTO roles (
    nombre,
    descripcion,
    role_type_id,
    home_route,
    requires_approval,
    status,
    display_order
) VALUES (
    'unidad_analisis',
    'Cliente Unidad de Análisis',
    2,
    'home.unidad_analisis',
    1,
    1,
    9
);

SET @new_id = (SELECT id_rol FROM roles WHERE nombre = 'unidad_analisis' LIMIT 1);

-- Permisos de Menú Cliente del rol oficial
INSERT IGNORE INTO customer_menu_permissions (
    id_rol,
    route_name,
    enabled,
    display_order,
    created_at,
    updated_at
)
SELECT
    @new_id,
    route_name,
    enabled,
    display_order,
    NOW(),
    NOW()
FROM customer_menu_permissions
WHERE id_rol = @oficial_id;

-- Asegurar que el rol tenga todos los items del catálogo de menú cliente
INSERT IGNORE INTO customer_menu_permissions (
    id_rol,
    route_name,
    enabled,
    display_order,
    created_at,
    updated_at
)
SELECT
    @new_id,
    route_name,
    1,
    default_display_order,
    NOW(),
    NOW()
FROM menu_items
WHERE scope = 'customer';

-- Permisos de secciones de servicios del rol oficial
INSERT IGNORE INTO provider_service_section_roles (
    provider_service_section_id,
    id_rol,
    status,
    created_at,
    updated_at
)
SELECT
    provider_service_section_id,
    @new_id,
    status,
    NOW(),
    NOW()
FROM provider_service_section_roles
WHERE id_rol = @oficial_id;

-- Servicios por Rol del rol oficial
INSERT IGNORE INTO provider_service_roles (
    id_rol,
    provider_service_id,
    status,
    created_at,
    updated_at
)
SELECT
    @new_id,
    provider_service_id,
    status,
    NOW(),
    NOW()
FROM provider_service_roles
WHERE id_rol = @oficial_id;
