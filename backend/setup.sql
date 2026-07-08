-- Script de Creación y Actualización de Base de Datos para Fase 5
-- Ejecutar en phpMyAdmin (Base de datos: solicitudes_istae)

-- 1. Tabla de Solicitudes (Añadiendo nuevos campos si existen, o creándola desde cero)
CREATE TABLE IF NOT EXISTS `solicitudes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(255) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `carrera` varchar(150) NOT NULL,
  `nivel` varchar(50) NOT NULL,
  `jornada` varchar(50) NOT NULL,
  `tramite` varchar(150) NOT NULL,
  `detalle` text NOT NULL,
  `contacto` varchar(150) NOT NULL,
  `estado` enum('PENDIENTE','EN REVISION','APROBADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
  `destinatario` varchar(150) DEFAULT NULL,
  `unidadOtra` varchar(255) DEFAULT NULL,
  `numero_fisico` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- En caso de que la tabla ya exista, intentamos agregar las columnas (Si da error porque ya existen, ignorarlo)
-- ALTER TABLE `solicitudes` ADD COLUMN `destinatario` varchar(150) DEFAULT NULL;
-- ALTER TABLE `solicitudes` ADD COLUMN `unidadOtra` varchar(255) DEFAULT NULL;
-- ALTER TABLE `solicitudes` ADD COLUMN `numero_fisico` varchar(50) DEFAULT NULL;

-- 2. Tabla de Usuarios (Para el Panel Administrativo)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` enum('ADMIN','SECRETARIA') NOT NULL DEFAULT 'SECRETARIA',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insertar usuario administrador por defecto (Clave: istae2026)
-- La clave está encriptada con password_hash de PHP
INSERT IGNORE INTO `usuarios` (`username`, `password`, `nombre`, `rol`) VALUES
('admin', '$2y$10$w/XyH5l.d8K8/p3oO1eP9.QZJ.3T/8/2B6rW3W9y4qX0w4qTzN95C', 'Administrador General', 'ADMIN'),
('secretaria', '$2y$10$w/XyH5l.d8K8/p3oO1eP9.QZJ.3T/8/2B6rW3W9y4qX0w4qTzN95C', 'Personal de Secretaría', 'SECRETARIA');
