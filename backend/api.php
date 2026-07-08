<?php
/**
 * API Backend para Asistente de Solicitudes ISTAE
 * - Genera un número de solicitud secuencial único.
 * - Envía una notificación por correo a Secretaría.
 */

// Permitir peticiones desde el mismo dominio o configurar CORS si es necesario
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Leer los datos enviados desde JavaScript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos o vacíos.']);
    exit;
}

// 2. Gestionar el Contador Global
$counterFile = __DIR__ . '/contador.txt';

// Si el archivo no existe, lo creamos empezando en 0
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, '0');
}

// Abrir el archivo de texto y bloquearlo para evitar que dos alumnos choquen al mismo tiempo
$fp = fopen($counterFile, 'r+');
$count = 0;
if (flock($fp, LOCK_EX)) {
    // Leer el número actual
    $size = filesize($counterFile);
    $current = $size > 0 ? (int)fread($fp, $size) : 0;
    
    // Incrementar
    $count = $current + 1;
    
    // Guardar el nuevo número
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string)$count);
    
    // Liberar el bloqueo
    flock($fp, LOCK_UN);
} else {
    // Fallback: si no se puede bloquear, usamos el timestamp
    $count = time(); 
}
fclose($fp);

// 3. Formatear el Código de la Solicitud (Ej: SOL-DS-2026-0001)
$secuencial = str_pad($count, 4, '0', STR_PAD_LEFT);
$sigla = isset($data['carrera_sigla']) ? $data['carrera_sigla'] : 'GEN';
$anio = date('Y');
$codigo = "SOL-{$sigla}-{$anio}-{$secuencial}";

// 4. Enviar notificación por correo (Opcional pero recomendado)
$to = 'secretaria@istae.edu.ec'; // <-- CAMBIA ESTO AL CORREO REAL DE SECRETARÍA
$subject = "Nueva Solicitud Web: {$codigo} - {$data['nombre']}";

$message = "Se ha generado una nueva solicitud desde el asistente virtual.\n\n";
$message .= "CÓDIGO: {$codigo}\n";
$message .= "Estudiante: {$data['nombre']}\n";
$message .= "Cédula: {$data['cedula']}\n";
$message .= "Carrera: {$data['carrera']}\n";
$message .= "Nivel: {$data['nivel']} ({$data['jornada']})\n";
$message .= "Trámite solicitado: {$data['tramite']}\n";
$message .= "Detalle proporcionado:\n{$data['detalle']}\n\n";
$message .= "Contacto del estudiante: {$data['contacto']}\n\n";
$message .= "---\nEste mensaje fue generado automáticamente por el Asistente de Solicitudes ISTAE.";

$headers = "From: no-reply@eva.istae.edu.ec\r\n" .
           "Reply-To: {$data['contacto']}\r\n" .
           "X-Mailer: PHP/" . phpversion();

// Suprimimos los errores de mail() con @ para que no rompa el JSON si el servidor no tiene SMTP configurado
@mail($to, $subject, $message, $headers);

// 5. Devolver el código a JavaScript
echo json_encode([
    'status' => 'success',
    'codigo' => $codigo
]);
?>
