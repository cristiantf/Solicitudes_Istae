<?php
/**
 * Conexión a la Base de Datos MySQL mediante PDO
 */

// ¡MODIFICA ESTOS DATOS CON LOS DE TU SERVIDOR WEBMIN!
$db_host = 'localhost';
$db_name = 'solicitudes_istae'; // Nombre de tu base de datos
$db_user = 'root';              // Tu usuario de BD
$db_pass = '';                  // Tu contraseña de BD

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
