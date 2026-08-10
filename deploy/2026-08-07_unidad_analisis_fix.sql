-- ============================================================
-- Fix: agregar permisos de menú cliente faltantes para unidad_analisis
-- ============================================================

SET @new_id = (SELECT id_rol FROM roles WHERE nombre = 'unidad_analisis' LIMIT 1);

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
