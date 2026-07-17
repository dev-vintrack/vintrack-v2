-- Crear tabla de vehículos consultados para VINTrack v2.
-- Ejecutar en PHPMyAdmin del hosting compartido.

CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint unsigned NOT NULL,
  `criterio` varchar(32) NOT NULL,
  `valor` varchar(64) NOT NULL,
  `marca` varchar(128) DEFAULT NULL,
  `modelo` varchar(128) DEFAULT NULL,
  `anio` varchar(16) DEFAULT NULL,
  `ultimo_status_robo` tinyint(1) NOT NULL DEFAULT '0',
  `total_consultas` int unsigned NOT NULL DEFAULT '0',
  `ultima_consulta_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_provider_valor_unique` (`provider_id`,`valor`),
  KEY `vehicles_ultima_consulta_at_index` (`ultima_consulta_at`),
  KEY `vehicles_ultimo_status_robo_index` (`ultimo_status_robo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
