<?php

/**
 * API para crear citas desde el dashboard
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Cita;
use App\Models\Cliente;
use App\Services\TwilioService;
use App\Services\GoogleCalendarService;
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
$clienteModel = new Cliente();
$twilioService = new TwilioService($config['twilio']);
$calendarService = new GoogleCalendarService($config['google_calendar']);

// Obtener datos del request
$data = json_decode(file_get_contents("php://input"), true);

$telefono = $data['telefono'] ?? null;
$servicio = $data['servicio'] ?? null;
$fecha = $data['fecha'] ?? null;

// Validar datos obligatorios
if (!$telefono || !$servicio || !$fecha) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Todos los campos son obligatorios"]);
    exit;
}

// Validar formato del teléfono (debe ser whatsapp:+11dígitos)
if (!preg_match('/^whatsapp:\+\d{11}$/', $telefono)) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Formato de teléfono inválido. Debe ser whatsapp:+11dígitos"]);
    exit;
}

// Crear objeto DateTime con la fecha recibida en zona horaria de México
try {
    $fechaObj = new DateTime($fecha, new DateTimeZone('America/Mexico_City'));
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Formato de fecha inválido"]);
    exit;
}

// Validar que la fecha y hora sean futuras
$ahora = new DateTime('now', new DateTimeZone('America/Mexico_City'));
if ($fechaObj <= $ahora) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "No se puede crear una cita en el pasado o en la hora actual"]);
    exit;
}

// Convertir a formato MySQL
$fechaMySQL = $fechaObj->format('Y-m-d H:i:s');

try {
    // Registrar el cliente si no existe (vincular teléfono con empresa)
    $clienteModel->registrar($idEmpresa, $telefono);
    
    // Obtener el ID del cliente recién registrado o existente
    $clienteData = $clienteModel->obtenerEmpresaPorTelefono($telefono);
    if (!$clienteData) {
        throw new Exception("No se pudo obtener el cliente");
    }
    
    // Crear en Google Calendar solo si está activado en la configuración
    if (isset($config['google_calendar']['activo']) && $config['google_calendar']['activo'] === true) {
        $calendarService->createAppointment($telefono, $fechaMySQL, $servicio);
    }

    // Guardar en base de datos con el idCliente
    if ($citaModel->crear($telefono, $fechaMySQL, $servicio, $clienteData['id'])) {
        // Enviar notificación por WhatsApp
        $fechaFormateada = $fechaObj->format('d/m/Y g:i A');
        $mensaje = "✅ ¡Tu cita ha sido agendada!\n\n"
                 . "📅 Fecha: {$fechaFormateada}\n"
                 . "💈 Servicio: {$servicio}\n\n"
                 . "Te enviaré un recordatorio 24 horas antes. ¡Nos vemos! 😊";

        $twilioService->sendMessage($telefono, $mensaje);

        header('Content-Type: application/json');
        echo json_encode(["ok" => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["ok" => false, "error" => "Error al guardar la cita en la base de datos"]);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Error: " . $e->getMessage()]);
}
