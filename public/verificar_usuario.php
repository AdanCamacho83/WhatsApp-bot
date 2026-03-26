<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Empresa;

// Cargar configuración e inicializar base de datos
$config = require __DIR__ . '/../config/config.php';
Database::setConfig($config['database']);

try {
    // Obtener el usuario a verificar
    $usuario = trim($_GET['usuario'] ?? '');
    
    if (empty($usuario)) {
        echo json_encode([
            "ok" => false,
            "disponible" => false,
            "error" => "Usuario requerido"
        ]);
        exit;
    }
    
    // Crear instancia del modelo
    $empresaModel = new Empresa();
    
    // Verificar si el usuario existe
    $existe = $empresaModel->existeUsuario($usuario);
    
    echo json_encode([
        "ok" => true,
        "disponible" => !$existe,
        "mensaje" => $existe ? "El usuario ya está registrado" : "Usuario disponible"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "ok" => false,
        "disponible" => false,
        "error" => "Error del servidor: " . $e->getMessage()
    ]);
}
