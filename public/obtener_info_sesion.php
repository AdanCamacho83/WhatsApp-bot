<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\SessionManager;
use App\Database\Database;
use App\Models\Empresa;

try {
    // Verificar sesión activa
    SessionManager::requerirSesion();
    
    // Obtener datos del usuario desde sesión
    $usuario = SessionManager::obtenerUsuario();
    
    if (!$usuario) {
        echo json_encode([
            'ok' => false,
            'error' => 'No hay sesión activa'
        ]);
        exit;
    }
    
    // Cargar configuración e inicializar base de datos
    $config = require __DIR__ . '/../config/config.php';
    Database::setConfig($config['database']);
    
    // Obtener información completa de la empresa
    $empresaModel = new Empresa();
    $empresa = $empresaModel->obtenerPorId($usuario['id']);
    
    if (!$empresa) {
        echo json_encode([
            'ok' => false,
            'error' => 'Empresa no encontrada'
        ]);
        exit;
    }
    
    echo json_encode([
        'ok' => true,
        'empresa' => [
            'id' => $empresa['id'],
            'nombre_empresa' => $empresa['nombre_empresa'],
            'codigo_empresa' => $empresa['codigo_empresa'],
            'usuario' => $empresa['usuario'],
            'telefono_contacto' => $empresa['telefono_contacto'],
            'telefono_twilio' => $empresa['telefono_twilio'],
            'direccion' => $empresa['direccion']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
