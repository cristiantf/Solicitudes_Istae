<?php
/**
 * API Backend para Consulta de Estado por Cédula
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['cedula'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Por favor, ingrese el número de cédula.']);
    exit;
}

require_once 'db.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'El sistema de consulta no está configurado (Falta BD).']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT codigo, fecha, nombre, tramite, estado FROM solicitudes WHERE cedula = ? ORDER BY fecha DESC");
    $stmt->execute([$data['cedula']]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($solicitudes) > 0) {
        // Formatear fechas
        foreach($solicitudes as &$solicitud) {
            $solicitud['fecha'] = date("d/m/Y H:i", strtotime($solicitud['fecha']));
        }
        echo json_encode($solicitudes);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontró ninguna solicitud con ese número de cédula.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}
?>
