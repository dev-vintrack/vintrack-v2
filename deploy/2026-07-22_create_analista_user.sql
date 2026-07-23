-- Crear usuario analista en PHPMyAdmin
-- Password en texto plano: Analist85199606?
-- El password ya está hasheado con bcrypt (Laravel Hash/Bcrypt)
-- Si ya existe un usuario con email analista@vintrack.com.mx, elimínalo primero o usa REPLACE INTO.

INSERT INTO `users` (
    `name`,
    `nombre`,
    `email`,
    `telefono`,
    `id_rol`,
    `status`,
    `activo`,
    `password`,
    `approved_at`,
    `email_verified_at`,
    `remember_token`,
    `created_at`,
    `updated_at`
) VALUES (
    'Analist Person',
    'Analista',
    'analista@vintrack.com.mx',
    '5612345678',
    2,
    'active',
    1,
    '$2y$12$qnMONLapQCF4/RrEFqpPV.XiAR2HLajReQiSNXdE2mOM1a7.7zEDW',
    NOW(),
    NULL,
    NULL,
    NOW(),
    NOW()
);
