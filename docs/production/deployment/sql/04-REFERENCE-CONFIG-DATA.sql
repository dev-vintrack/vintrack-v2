-- VINTrack PG-01: approved data backfill and reference/configuration data.
-- APPLY ONCE after 02/03. Review conflict queries first and STOP on non-zero conflicts.

SELECT COUNT(*) AS customer_menu_conflicts
FROM menu_items WHERE route_name='customer.notification-cases.index'
  AND (scope<>'customer' OR label<>'Proceso de Notificaciones' OR icon<>'file-earmark-text' OR default_display_order<>4);
SELECT COUNT(*) AS admin_menu_conflicts
FROM menu_items WHERE route_name='admin.notification-cases.index'
  AND (scope<>'admin' OR label<>'Proceso de Notificaciones' OR icon<>'clipboard2-check' OR default_display_order<>3);

-- Required historical identity backfill. This updates every consultation lacking a normalized value.
UPDATE consultations
SET normalized_value=UPPER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(valor),'-',''),' ',''),'.',''),'/',''))
WHERE LOWER(criterio)='placa' AND normalized_value IS NULL;
UPDATE consultations SET normalized_value=UPPER(TRIM(valor)) WHERE normalized_value IS NULL;

-- Eight settings are singleton columns, not key/value rows. ALTER defaults populate the existing row safely.
SELECT notification_case_deadline_days, notification_case_max_open_days,
       notification_case_reuse_days, notification_case_max_pending,
       notification_case_max_files, notification_case_max_file_bytes,
       notification_case_timezone, notification_case_reservation_ttl_seconds
FROM global_configuration;

INSERT INTO menu_items (route_name,scope,label,icon,default_display_order,created_at,updated_at)
SELECT 'customer.notification-cases.index','customer','Proceso de Notificaciones','file-earmark-text',4,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE route_name='customer.notification-cases.index');
INSERT INTO menu_items (route_name,scope,label,icon,default_display_order,created_at,updated_at)
SELECT 'admin.notification-cases.index','admin','Proceso de Notificaciones','clipboard2-check',3,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE route_name='admin.notification-cases.index');

INSERT INTO customer_menu_permissions (id_rol,route_name,enabled,display_order,created_at,updated_at)
SELECT r.id_rol,'customer.notification-cases.index',1,4,NOW(),NOW()
FROM roles r JOIN role_types rt ON rt.id=r.role_type_id
WHERE rt.is_customer=1 AND r.nombre IN ('cliente_registrado','perito','oficial','unidad_analisis')
  AND NOT EXISTS (SELECT 1 FROM customer_menu_permissions p WHERE p.id_rol=r.id_rol AND p.route_name='customer.notification-cases.index');
INSERT INTO admin_menu_permissions (id_rol,route_name,enabled,display_order,created_at,updated_at)
SELECT r.id_rol,'admin.notification-cases.index',1,3,NOW(),NOW()
FROM roles r WHERE r.nombre IN ('admin','analista')
  AND NOT EXISTS (SELECT 1 FROM admin_menu_permissions p WHERE p.id_rol=r.id_rol AND p.route_name='admin.notification-cases.index');

UPDATE menu_items SET label='Historial de Vehículos Consultados',icon='clock-history',updated_at=NOW()
WHERE route_name='customer.consultations';
UPDATE menu_items SET label='Historial Global de Vehículos Consultados',icon='clipboard-data',updated_at=NOW()
WHERE route_name='admin.consultations.index';
INSERT INTO admin_menu_permissions (id_rol,route_name,enabled,display_order,created_at,updated_at)
SELECT r.id_rol,'admin.consultations.index',1,4,NOW(),NOW() FROM roles r
WHERE r.nombre IN ('admin','analista')
  AND NOT EXISTS (SELECT 1 FROM admin_menu_permissions p WHERE p.id_rol=r.id_rol AND p.route_name='admin.consultations.index');
UPDATE admin_menu_permissions p JOIN roles r ON r.id_rol=p.id_rol
SET p.enabled=0,p.updated_at=NOW()
WHERE r.nombre='soporte' AND p.route_name='admin.consultations.index';
