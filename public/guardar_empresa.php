<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Empresa;

// Cargar configuración e inicializar base de datos
$config = require __DIR__ . '/../config/config.php';

// Inicializar base de datos
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

    // Obtener y validar datos del formulario
    $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
    $telefonoContacto = trim($_POST['telefono_contacto'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    // Validar campos obligatorios
    if (empty($nombreEmpresa)) {
        echo json_encode([
            "ok" => false,
            "error" => "El nombre de la empresa es obligatorio"
        ]);
        exit;
    }

    if (empty($telefonoContacto)) {
        echo json_encode([
            "ok" => false,
            "error" => "El teléfono de contacto es obligatorio"
        ]);
        exit;
    }

    if (empty($usuario)) {
        echo json_encode([
            "ok" => false,
            "error" => "El usuario es obligatorio"
        ]);
        exit;
    }

    // Validar formato de usuario (solo alfanumérico, guiones y guiones bajos)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        echo json_encode([
            "ok" => false,
            "error" => "El usuario solo puede contener letras, números, guiones y guiones bajos"
        ]);
        exit;
    }

    if (empty($password)) {
        echo json_encode([
            "ok" => false,
            "error" => "La contraseña es obligatoria"
        ]);
        exit;
    }

    // Validar requisitos de seguridad de la contraseña
    if (strlen($password) < 8) {
        echo json_encode([
            "ok" => false,
            "error" => "La contraseña debe tener al menos 8 caracteres"
        ]);
        exit;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode([
            "ok" => false,
            "error" => "La contraseña debe contener al menos una letra mayúscula"
        ]);
        exit;
    }

    if (!preg_match('/[a-z]/', $password)) {
        echo json_encode([
            "ok" => false,
            "error" => "La contraseña debe contener al menos una letra minúscula"
        ]);
        exit;
    }

    if (!preg_match('/[0-9]/', $password)) {
        echo json_encode([
            "ok" => false,
            "error" => "La contraseña debe contener al menos un número"
        ]);
        exit;
    }

    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        echo json_encode([
            "ok" => false,
            "error" => "La contraseña debe contener al menos un carácter especial (!@#$%^&*...)"
        ]);
        exit;
    }

    // Validar que las contraseñas coincidan
    if ($password !== $confirmarPassword) {
        echo json_encode([
            "ok" => false,
            "error" => "Las contraseñas no coinciden"
        ]);
        exit;
    }

    // Validar longitud máxima de campos
    if (strlen($nombreEmpresa) > 100) {
        echo json_encode([
            "ok" => false,
            "error" => "El nombre de la empresa no puede exceder 100 caracteres"
        ]);
        exit;
    }

    if (strlen($telefonoContacto) > 40) {
        echo json_encode([
            "ok" => false,
            "error" => "El teléfono de contacto no puede exceder 40 caracteres"
        ]);
        exit;
    }

    if (strlen($direccion) > 150) {
        echo json_encode([
            "ok" => false,
            "error" => "La dirección no puede exceder 150 caracteres"
        ]);
        exit;
    }

    if (strlen($usuario) > 150) {
        echo json_encode([
            "ok" => false,
            "error" => "El usuario no puede exceder 150 caracteres"
        ]);
        exit;
    }

    // Encriptar contraseña usando bcrypt (método más seguro)
    // PASSWORD_DEFAULT usa bcrypt, que es el estándar recomendado
    // El cost factor de 12 proporciona un buen balance entre seguridad y rendimiento
    $passwordEncriptado = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

    if ($passwordEncriptado === false) {
        echo json_encode([
            "ok" => false,
            "error" => "Error al encriptar la contraseña"
        ]);
        exit;
    }

    // Crear instancia del modelo
    $empresaModel = new Empresa();

    // Verificar si el usuario ya existe
    if ($empresaModel->existeUsuario($usuario)) {
        echo json_encode([
            "ok" => false,
            "error" => "El nombre de usuario ya está registrado. Por favor, elija otro."
        ]);
        exit;
    }

    // Generar código único para la empresa usando EmpresaCodeManager
    $codigoEmpresa = \App\Utils\EmpresaCodeManager::generarCodigo($nombreEmpresa);
    
    // Verificar que el código sea único
    $intentos = 0;
    while ($empresaModel->obtenerPorCodigo($codigoEmpresa) && $intentos < 10) {
        $codigoEmpresa = \App\Utils\EmpresaCodeManager::generarCodigo($nombreEmpresa . $intentos);
        $intentos++;
    }
    
    // Número de WhatsApp Twilio (sandbox por defecto)
    $telefonoTwilio = $config['twilio']['whatsapp_number'] ?? '+14155238886';

    // Guardar en la base de datos
    $resultado = $empresaModel->crear(
        $nombreEmpresa,
        $codigoEmpresa,
        $telefonoContacto,
        $telefonoTwilio,
        $direccion,
        $usuario,
        $passwordEncriptado
    );

    if ($resultado) {
        echo json_encode([
            "ok" => true,
            "mensaje" => "Empresa registrada exitosamente",
            "codigo_empresa" => $codigoEmpresa
        ]);
    } else {
        echo json_encode([
            "ok" => false,
            "error" => "Error al guardar la empresa en la base de datos"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "ok" => false,
        "error" => "Error del servidor: " . $e->getMessage()
    ]);
}
