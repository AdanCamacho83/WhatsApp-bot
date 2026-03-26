<?php

/**
 * Script para enviar recordatorios automáticos
 * Debe ejecutarse mediante un cronjob
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Models\Cita;
use App\Services\TwilioService;
use DateTime;
use DateTimeZone;

// Cargar configuración
$config = require __DIR__ . '/../config/config.php';

// Inicializar base de datos
Database::setConfig($config['database']);

// Inicializar servicios
$citaModel = new Cita();
$twilioService = new TwilioService($config['twilio']);

// Calcular ventana de tiempo (entre 15 y 24 horas antes de la cita)
$ahora = new DateTime('now', new DateTimeZone($config['timezone']));
$desde = (clone $ahora)->modify('+15 hours')->format('Y-m-d H:i:s');
$hasta = (clone $ahora)->modify('+24 hours')->format('Y-m-d H:i:s');

// Debug: mostrar query
echo "Buscando citas entre:\n";
echo "Desde: $desde\n";
echo "Hasta: $hasta\n\n";

// Obtener citas pendientes de recordatorio
$citas = $citaModel->obtenerPendientesRecordatorio($desde, $hasta);

echo "Se encontraron " . count($citas) . " citas pendientes de recordatorio.\n\n";

// Enviar recordatorios
foreach ($citas as $cita) {
    $mensaje = "⏰ Recordatorio de tu cita\n\n"
             . "🗓️ Mañana\n"
             . "⏰ " . date('g:i A', strtotime($cita['fecha_inicio'])) . "\n"
             . "💈 " . $cita['servicio'];

    echo "Enviando recordatorio a: {$cita['telefono_usuario']}\n";
    
    // Enviar WhatsApp
    if ($twilioService->sendMessage($cita['telefono_usuario'], $mensaje)) {
        // Marcar como enviado
        $citaModel->marcarRecordatorioEnviado($cita['id']);
        echo "✓ Recordatorio enviado exitosamente\n\n";
    } else {
        echo "✗ Error al enviar recordatorio\n\n";
    }
}

echo "Proceso completado.\n";

// =======================
// CÓDIGO LEGACY (COMENTADO PARA REFERENCIA)
// =======================
/*
function twilio() {
  return new Client(
    'YOUR_TWILIO_ACCOUNT_SID',
    'YOUR_TWILIO_AUTH_TOKEN'
  );
}

function enviarWhatsApp($to, $mensaje) {

  $client = twilio();

  $client->messages->create(
    $to,
    [
      'from' => 'whatsapp:+14155238886',
      'body' => $mensaje
    ]
  );
}
*/

