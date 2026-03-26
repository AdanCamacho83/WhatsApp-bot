<?php
date_default_timezone_set('UTC');
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\SessionManager;
use App\Database\Database;
use App\Models\Horario;

try {
    // Requerir sesión activa
    SessionManager::requerirSesion();
    
    // Validar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'ok' => false,
            'error' => 'Método no permitido'
        ]);
        exit;
    }
    
    // Obtener datos del usuario
    $usuario = SessionManager::obtenerUsuario();
    $idEmpresa = $usuario['id'];
    
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode([
            'ok' => false,
            'error' => 'Datos inválidos'
        ]);
        exit;
    }
    
    // Validar campos requeridos
    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    $horarios = [];
    
    foreach ($dias as $dia) {
        $apertura = $data["{$dia}_apertura"] ?? '00:00:00';
        $cierre = $data["{$dia}_cierre"] ?? '00:00:00';
        
        $horarios["{$dia}_apertura"] = $apertura;
        $horarios["{$dia}_cierre"] = $cierre;
    }
    
    $horarios['tiempo_atencion'] = $data['tiempo_atencion'] ?? '00:00:00';
    
    // Función para convertir HH:MM:SS a segundos (UTC)
    function timeToSeconds($time) {
        $parts = explode(':', $time);
        return ($parts[0] * 3600) + ($parts[1] * 60) + ($parts[2] ?? 0);
    }
    
    // Validaciones
    $tiempoAtencion = timeToSeconds($horarios['tiempo_atencion']);
    
    // Validar que tiempo_atencion no esté vacío
    if ($tiempoAtencion <= 0) {
        echo json_encode([
            'ok' => false,
            'error' => 'El tiempo de atención debe ser mayor a 00:00:00'
        ]);
        exit;
    }
  
    // Validar tiempo_atencion esté dentro de la diferencia entre cierre y apertura
    foreach ($dias as $dia) {
        $aperturaSegundos = timeToSeconds($horarios["{$dia}_apertura"]);
        $cierreSegundos = timeToSeconds($horarios["{$dia}_cierre"]);
        
        // Solo validar días con horario (no días de descanso)
        if ($aperturaSegundos > 0 || $cierreSegundos > 0) {
            $diferenciaSegundos = $cierreSegundos - $aperturaSegundos;
            if ($tiempoAtencion > $diferenciaSegundos) {
                echo json_encode([
                    'ok' => false,
                    'error' => "El tiempo de atención excede el horario disponible del " . ucfirst($dia)
                ]);
                exit;
            }
        }
    }
    
    // Cargar configuración e inicializar base de datos
    $config = require __DIR__ . '/../config/config.php';
    Database::setConfig($config['database']);
    
    // Guardar horarios
    $horarioModel = new Horario();
    $resultado = $horarioModel->guardar($idEmpresa, $horarios);
    
    if ($resultado) {
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Horarios guardados exitosamente'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'error' => 'Error al guardar los horarios'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
