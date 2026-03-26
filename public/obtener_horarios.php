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
    
    // Obtener datos del usuario
    $usuario = SessionManager::obtenerUsuario();
    $idEmpresa = $usuario['id'];
    
    // Cargar configuración e inicializar base de datos
    $config = require __DIR__ . '/../config/config.php';
    Database::setConfig($config['database']);
    
    // Obtener horarios
    $horarioModel = new Horario();
    $horarios = $horarioModel->obtenerPorEmpresa($idEmpresa);
    
    echo json_encode([
        'ok' => true,
        'horarios' => $horarios
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
