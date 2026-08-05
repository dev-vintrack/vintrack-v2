-- deploy/2026-08-04_add_vin_decoder_customer_menu_permission.sql
-- Agrega la opción "VIN Decoder" al menú de clientes para los roles habilitados.
-- Ejecutar desde phpMyAdmin (MariaDB 10.6) en el hosting.

-- UP
INSERT INTO customer_menu_permissions (id_rol, route_name, label, icon, enabled, display_order, created_at, updated_at)
SELECT id_rol,
       'customer.vin-decoder',
       'VIN Decoder',
       'upc-scan',
       1,
       3,
       NOW(),
       NOW()
FROM roles
WHERE nombre IN ('cliente_registrado', 'perito', 'oficial')
  AND NOT EXISTS (
      SELECT 1
      FROM customer_menu_permissions cmp
      WHERE cmp.id_rol = roles.id_rol
        AND cmp.route_name = 'customer.vin-decoder'
  );

-- DOWN (descomenta si necesitas revertir)
-- DELETE FROM customer_menu_permissions WHERE route_name = 'customer.vin-decoder';
