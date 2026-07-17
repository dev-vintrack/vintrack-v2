-- Crear usuario cliente demo para VINTrack
-- Ejecutar en PHPMyAdmin del hosting compartido.
-- Si el email ya existe, INSERT IGNORE lo omitirá y no modificará el registro actual.

INSERT IGNORE INTO `users`
(`name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `nombre`, `telefono`, `rol`, `activo`, `approved_at`, `status`)
VALUES
('Cliente Demo', 'cliente.demo@vintrack.com.mx', NOW(), '$2y$12$PeG.QrWktF/.iEvyP0DmS.cPueWPhymZLd714KpmppVQZKccMufFS', NULL, NOW(), NOW(), 'Cliente Demo', NULL, 'cliente_registrado', 1, NOW(), 'active');
