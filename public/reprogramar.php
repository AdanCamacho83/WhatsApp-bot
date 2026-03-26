<?php

/**
 * API para reprogramar citas desde el dashboard
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Cita;
use App\Services\TwilioService;
use App\Utils\SessionManager;

// Requerir sesión activa
SessionManager::requerirSesion();

// Obtener datos del usuario
$usuario = SessionManager::obtenerUsuario();
$idEmpresa = $usuario['id'];

// Cargar configuración
$config = require __DIR__ . '/../config/config.php';

// Inicializar base de datos
Database::setConfig($config['database']);

// Inicializar servicios
$citaModel = new Cita();
$twilioService = new TwilioService($config['twilio']);

// Obtener datos del request
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$fecha = $data['fecha'] ?? null;

// Validar datos
if (!$id || !$fecha) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Datos incompletos"]);
    exit;
}

// Crear objeto DateTime con la fecha recibida en zona horaria de México
try {
    $fechaNuevaObj = new DateTime($fecha, new DateTimeZone('America/Mexico_City'));
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Formato de fecha inválido"]);
    exit;
}

// Obtener hora actual en zona horaria de México
$ahora = new DateTime('now', new DateTimeZone('America/Mexico_City'));

// Validar que la fecha y hora sean futuras (no iguales ni pasadas)
if ($fechaNuevaObj <= $ahora) {
    $horaActual = $ahora->format('d/m/Y g:i A');
    $horaIntentada = $fechaNuevaObj->format('d/m/Y g:i A');
    header('Content-Type: application/json');
    echo json_encode([
        "ok" => false, 
        "error" => "No se puede reprogramar a una fecha u hora pasada.\n\nHora actual: {$horaActual}\nHora seleccionada: {$horaIntentada}"
    ]);
    exit;
}

// Convertir a formato MySQL
$fechaNueva = $fechaNuevaObj->format('Y-m-d H:i:s');

// Obtener información de la cita (solo si pertenece a la empresa actual)
$cita = $citaModel->obtenerPorId($id, $idEmpresa);

if (!$cita) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Cita no encontrada o no tienes permiso para acceder a ella"]);
    exit;
}
    echo json_encode(["ok" => false, "error" => "Cita no encontrada"]);
    exit;
}

// Validar que la cita no esté cancelada
if ($cita['estado'] === 'cancelada') {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "No se puede reprogramar una cita cancelada"]);
    exit;
}

// Actualizar la cita
if ($citaModel->actualizar($id, $fechaNueva)) {
    // Enviar notificación por WhatsApp
    $mensaje = "🔁 Tu cita ha sido reprogramada\n\n"
             . "📅 Nueva fecha:\n"
             . date('d/m/Y g:i A', strtotime($fechaNueva)) . "\n\n"
             . "💈 Servicio: {$cita['servicio']}";

    $twilioService->sendMessage($cita['telefono_usuario'], $mensaje);

    header('Content-Type: application/json');
    echo json_encode(["ok" => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Error al actualizar"]);
}