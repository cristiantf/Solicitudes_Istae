<?php
require_once 'db.php';

try {
    // Añadir columnas si no existen (Ignorando errores si ya existen usando try/catch)
    $columns = ['destinatario', 'unidadOtra', 'numero_fisico'];
    foreach ($columns as $col) {
        try {
            $pdo->exec("ALTER TABLE solicitudes ADD COLUMN $col VARCHAR(255) DEFAULT NULL");
            echo "Columna $col agregada.\n";
        } catch(PDOException $e) {
            // Probably column already exists, ignore.
        }
    }

    // Crear tabla de usuarios
    $sqlUsuarios = "
    CREATE TABLE IF NOT EXISTS `usuarios` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(255) NOT NULL,
      `nombre` varchar(100) NOT NULL,
      `rol` enum('ADMIN','SECRETARIA') NOT NULL DEFAULT 'SECRETARIA',
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sqlUsuarios);
    echo "Tabla usuarios creada.\n";

    // Insertar usuarios base
    $sqlInsert = "
    INSERT IGNORE INTO `usuarios` (`username`, `password`, `nombre`, `rol`) VALUES
    ('admin', '\$2y\$10\$w/XyH5l.d8K8/p3oO1eP9.QZJ.3T/8/2B6rW3W9y4qX0w4qTzN95C', 'Administrador General', 'ADMIN'),
    ('secretaria', '\$2y\$10\$w/XyH5l.d8K8/p3oO1eP9.QZJ.3T/8/2B6rW3W9y4qX0w4qTzN95C', 'Personal de Secretaría', 'SECRETARIA');
    ";
    $pdo->exec($sqlInsert);
    echo "Usuarios insertados.\n";

    echo "Actualización de BD exitosa.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
