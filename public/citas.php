<?php

/**
 * API para obtener citas del calendario
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Cita;
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

// Inicializar modelo
$citaModel = new Cita();

// Obtener citas de los últimos 30 días SOLO de la empresa actual
$desde = date('Y-m-d', strtotime('-30 days'));
$citas = $citaModel->obtenerPorRango($desde, '', $idEmpresa);

// Preparar eventos para FullCalendar (solo mostrar citas activas)
$eventos = [];
foreach ($citas as $cita) {
    // Solo incluir citas activas
    if ($cita['estado'] === 'activa') {
        $eventos[] = [
            "id" => $cita['id'],
            "title" => $cita['servicio'],
            "start" => $cita['fecha_inicio'],
            "color" => '#198754',
            "extendedProps" => [
                "telefono" => $cita['telefono_usuario'] ?? '',
                "estado" => $cita['estado']
            ]
        ];
    }
}

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($eventos);

