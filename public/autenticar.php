<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Empresa;

// Cargar configuración e inicializar base de datos
$config = require __DIR__ . '/../config/config.php';
Database::setConfig($config['database']);

try {
    // Validar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            "ok" => false,
            "error" => "Método no permitido"
        ]);
        exit;
    }

    // Obtener credenciales
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $recordarme = isset($_POST['recordarme']) && $_POST['recordarme'] === 'on';

    // Validar campos obligatorios
    if (empty($usuario) || empty($password)) {
        echo json_encode([
            "ok" => false,
            "error" => "Usuario y contraseña son obligatorios"
        ]);
        exit;
    }

    // Crear instancia del modelo
    $empresaModel = new Empresa();

    // Verificar credenciales
    $empresa = $empresaModel->verificarCredenciales($usuario, $password);

    if (!$empresa) {
        // Registrar intento fallido (para seguridad adicional)
        error_log("Intento de login fallido para usuario: " . $usuario . " desde IP: " . $_SERVER['REMOTE_ADDR']);
        
        echo json_encode([
            "ok" => false,
            "error" => "Usuario o contraseña incorrectos"
        ]);
        exit;
    }

    // Regenerar ID de sesión para prevenir fijación de sesión
    session_regenerate_id(true);

    // Guardar información en la sesión
    $_SESSION['usuario_id'] = $empresa['id'];
    $_SESSION['usuario_nombre'] = $empresa['usuario'];
    $_SESSION['empresa_nombre'] = $empresa['nombre_empresa'];
    $_SESSION['empresa_telefono'] = $empresa['telefono_contacto'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Si el usuario marcó "recordarme", extender la sesión
    if ($recordarme) {
        // Establecer cookie de sesión por 30 días
        $sessionName = session_name();
        $sessionId = session_id();
        setcookie($sessionName, $sessionId, [
            'expires' => time() + (30 * 24 * 60 * 60), // 30 días
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    // Registrar login exitoso
    error_log("Login exitoso para usuario: " . $usuario . " (ID: " . $empresa['id'] . ") desde IP: " . $_SERVER['REMOTE_ADDR']);

    echo json_encode([
        "ok" => true,
        "mensaje" => "Inicio de sesión exitoso",
        "usuario" => [
            "id" => $empresa['id'],
            "nombre" => $empresa['usuario'],
            "empresa" => $empresa['nombre_empresa']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en autenticación: " . $e->getMessage());
    echo json_encode([
        "ok" => false,
        "error" => "Error del servidor. Por favor, intente nuevamente."
    ]);
}
