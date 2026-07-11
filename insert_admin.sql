-- Insertar/actualizar usuario admin (PHPMyAdmin compatible, sin ON DUPLICATE KEY UPDATE)
-- Password: Vintrack.2024!Admin
-- Ejecutar en la pestana SQL de PHPMyAdmin sobre la BD del hosting.

-- 1) Insertar admin solo si no existe
INSERT INTO users (name, email, password, nombre, telefono, rol, activo, approved_at, created_at, updated_at)
SELECT
    'Administrador',
    'admin@vintrack.com.mx',
    '$2y$12$6CZh3E2e61IG8slQMfSc9.16V4xwtcL2oOU6W/dKuIy.y75mVVYUu',
    'Administrador',
    '',
    'admin',
    1,
    NOW(),
    NOW(),
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM users WHERE email = 'admin@vintrack.com.mx') AS u);

-- 2) Si el usuario ya existia, asegurar password/estado correctos
UPDATE users
SET
    password = '$2y$12$6CZh3E2e61IG8slQMfSc9.16V4xwtcL2oOU6W/dKuIy.y75mVVYUu',
    rol = 'admin',
    activo = 1,
    approved_at = NOW(),
    updated_at = NOW()
WHERE email = 'admin@vintrack.com.mx';
