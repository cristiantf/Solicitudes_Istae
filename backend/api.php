<?php
/**
 * API Backend para Asistente de Solicitudes ISTAE (Versión con Base de Datos)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o vacíos.']);
    exit;
}

// 1. Conectar a la Base de Datos
require_once 'db.php';

if (!isset($pdo)) {
    // Fallback: Si no hay base de datos configurada, simulamos un ID basado en el tiempo
    // Esto evita que el frontend se rompa si el administrador aún no crea la tabla
    $count = time() % 10000;
    $codigo = "SOL-" . ($data['carrera_sigla'] ?? 'GEN') . "-" . date('Y') . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    echo json_encode(['status' => 'success', 'codigo' => $codigo, 'warning' => 'BD no configurada']);
    exit;
}

// 2. Insertar en la Base de Datos
try {
    // Bloquear tabla para evitar conflictos de concurrencia al leer el MAX(id)
    $pdo->exec("LOCK TABLES solicitudes WRITE");
    
    // Obtener el siguiente número secuencial (podemos basarnos en el ID o contar registros del año)
    // Para mantenerlo simple, usaremos el AUTO_INCREMENT ID que generará la BD.
    
    // Generamos un código temporal
    $sigla = isset($data['carrera_sigla']) ? $data['carrera_sigla'] : 'GEN';
    $anio = date('Y');
    
    // Primero insertamos el registro
    $stmt = $pdo->prepare("INSERT INTO solicitudes (codigo, nombre, cedula, carrera, nivel, jornada, tramite, detalle, contacto, destinatario, unidadOtra) VALUES (:codigo, :nombre, :cedula, :carrera, :nivel, :jornada, :tramite, :detalle, :contacto, :destinatario, :unidadOtra)");
    
    // Pasamos un código temporal (luego lo actualizamos con el ID real)
    $codigo_temp = "TEMP-" . uniqid();
    
    $stmt->execute([
        ':codigo' => $codigo_temp,
        ':nombre' => $data['nombre'],
        ':cedula' => $data['cedula'],
        ':carrera' => $data['carrera'],
        ':nivel' => $data['nivel'],
        ':jornada' => $data['jornada'],
        ':tramite' => $data['tramite'],
        ':detalle' => $data['detalle'],
        ':contacto' => $data['contacto'],
        ':destinatario' => $data['destinatario'] ?? null,
        ':unidadOtra' => $data['unidadOtra'] ?? null
    ]);
    
    $insertedId = $pdo->lastInsertId();
    
    // Actualizamos el código oficial
    $secuencial = str_pad($insertedId, 4, '0', STR_PAD_LEFT);
    $codigo = "SOL-{$sigla}-{$anio}-{$secuencial}";
    
    $pdo->prepare("UPDATE solicitudes SET codigo = ? WHERE id = ?")->execute([$codigo, $insertedId]);
    
    $pdo->exec("UNLOCK TABLES");
    
} catch (PDOException $e) {
    // Desbloquear por seguridad si ocurre error
    $pdo->exec("UNLOCK TABLES");
    error_log("Error guardando solicitud: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno guardando la solicitud en la base de datos.']);
    exit;
}

// 3. Enviar notificación por correo
$to = 'secretaria@istae.edu.ec'; // <-- CAMBIA ESTO
$subject = "Nueva Solicitud Web: {$codigo} - {$data['nombre']}";

$message = "Se ha generado una nueva solicitud desde el asistente virtual y está PENDIENTE en el sistema.\n\n";
$message .= "CÓDIGO: {$codigo}\n";
$message .= "Estudiante: {$data['nombre']}\n";
$message .= "Cédula: {$data['cedula']}\n";
$message .= "Carrera: {$data['carrera']}\n";
$message .= "Trámite solicitado: {$data['tramite']}\n";
$message .= "Contacto: {$data['contacto']}\n\n";
$message .= "Ingresa al panel administrativo para revisarla.";

$headers = "From: no-reply@eva.istae.edu.ec\r\n" .
           "Reply-To: {$data['contacto']}\r\n" .
           "X-Mailer: PHP/" . phpversion();

@mail($to, $subject, $message, $headers);

// 4. Devolver el código a JavaScript
echo json_encode([
    'status' => 'success',
    'codigo' => $codigo
]);
?>
