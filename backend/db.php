<?php
/**
 * Conexión a la Base de Datos MySQL mediante PDO
 */

// ¡MODIFICA ESTOS DATOS CON LOS DE TU SERVIDOR WEBMIN!
$db_host = 'localhost';
$db_name = 'solicitudes_istae'; // Nombre de la BD que creaste en Webmin
$db_user = 'root';              // Usuario de tu servidor Moodle
$db_pass = 'luislopez';         // Contraseña de tu servidor Moodle

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Habilitar el reporte de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si falla la conexión, mostramos un error genérico para no exponer contraseñas en JSON
    error_log("Error de conexión a la BD: " . $e->getMessage());
    // No matamos el script aquí para permitir que api.php maneje el error
}
?>
