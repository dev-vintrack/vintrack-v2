-- ============================================================
-- Sprint 4: Roles adicionales y campo status de aprobación
-- Ejecutar en PHPMyAdmin sobre la base de datos de VINTrack v2
-- ============================================================

-- Agregar columna status para aprobación de Perito/Oficial
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `rol`;

-- Asegurar que el usuario admin tenga el rol correcto (descomenta si tu admin actual tiene otro rol)
-- UPDATE `users` SET `rol` = 'admin', `status` = 'active', `activo` = 1 WHERE `email` = 'admin@vintrack.com.mx';

-- Normalizar usuarios con rol viejo 'usuario' a 'cliente_registrado' (descomenta si aplica)
-- UPDATE `users` SET `rol` = 'cliente_registrado', `status` = 'active' WHERE `rol` = 'usuario';
