-- Crear usuario perito en PHPMyAdmin
-- Password en texto plano: Perito51964873?
-- El password ya está hasheado con bcrypt (Laravel Hash/Bcrypt)
-- Si ya existe un usuario con email perito@vintrack.com.mx, elimínalo primero o usa REPLACE INTO.

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
    'José Luis Perito',
    'Perito de Pruebas',
    'perito@vintrack.com.mx',
    '5687654321',
    5,
    'active',
    1,
    '$2y$12$VJvHbPhXmos8Juea2G0Ch.r1cffsftZUovfCE7Gy5ERGASb9z6Lqy',
    NOW(),
    NULL,
    NULL,
    NOW(),
    NOW()
);
