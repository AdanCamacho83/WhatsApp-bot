<?php

/**
 * API para cancelar citas desde el dashboard
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

// Validar datos
if (!$id) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "ID de cita requerido"]);
    exit;
}

// Obtener información de la cita (solo si pertenece a la empresa actual)
$cita = $citaModel->obtenerPorId($id, $idEmpresa);

if (!$cita) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Cita no encontrada o no tienes permiso para acceder a ella"]);
    exit;
}

// Validar que la cita no esté ya cancelada
if ($cita['estado'] === 'cancelada') {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Esta cita ya fue cancelada"]);
    exit;
}

// Cancelar la cita - actualizar estado usando el método actualizar
try {
    $stmt = Database::getConnection()->prepare("
        UPDATE citas 
        SET estado = 'cancelada' 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$id])) {
        // Enviar notificación por WhatsApp
        $fechaFormateada = date('d/m/Y g:i A', strtotime($cita['fecha_inicio']));
        $mensaje = "❌ Tu cita ha sido cancelada\n\n"
                 . "📅 Fecha cancelada: {$fechaFormateada}\n"
                 . "💈 Servicio: {$cita['servicio']}\n\n"
                 . "Si deseas agendar una nueva cita, solo escríbeme 😊";

        $twilioService->sendMessage($cita['telefono_usuario'], $mensaje);

        header('Content-Type: application/json');
        echo json_encode(["ok" => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["ok" => false, "error" => "Error al cancelar la cita"]);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => false, "error" => "Error: " . $e->getMessage()]);
}
