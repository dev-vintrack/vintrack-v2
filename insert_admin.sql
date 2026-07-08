-- Insertar o actualizar usuario admin en hosting/local
-- Password: Vintrack.2024!Admin

INSERT INTO users (name, email, password, nombre, telefono, rol, activo, approved_at, created_at, updated_at)
VALUES (
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
)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    activo = VALUES(activo),
    approved_at = VALUES(approved_at),
    updated_at = NOW();
