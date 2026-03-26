<?php

/**
 * Webhook principal para WhatsApp
 * Este archivo recibe los mensajes de Twilio y procesa las conversaciones
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Services\TwilioService;
use App\Services\GoogleCalendarService;
use App\Services\OpenAIService;
use App\Controllers\WhatsAppController;

// Cargar configuración
$config = require __DIR__ . '/../config/config.php';

// Inicializar base de datos
Database::setConfig($config['database']);

// Inicializar servicios
$twilioService = new TwilioService($config['twilio']);
$calendarService = new GoogleCalendarService($config['google_calendar']);
$openAIService = new OpenAIService($config['openai']);

// Inicializar controlador
$controller = new WhatsAppController(
    $twilioService,
    $calendarService,
    $openAIService,
    $config
);

// Obtener datos del webhook
$telefono = $_POST['From'] ?? '';
$mensaje = strtolower(trim($_POST['Body'] ?? ''));

// Procesar mensaje y obtener respuesta
$respuesta = $controller->procesarMensaje($telefono, $mensaje);

// Enviar respuesta a Twilio
$twilioService->generateResponse($respuesta);

// =======================
// CÓDIGO LEGACY (COMENTADO PARA REFERENCIA)
// Eliminar después de verificar que todo funciona correctamente
// =======================
/*
function getCalendarService() {
  $client = new Client();
  $client->setAuthConfig(__DIR__ . '/../keys/calendar.json');
  $client->setScopes([Calendar::CALENDAR]);
  return new Calendar($client);
}

function crearCitaCalendar($calendarId, $telefono, $fechaHora) {
  $service = getCalendarService();

  $inicio = new DateTime($fechaHora, new DateTimeZone('America/Chicago'));
  $fin = (clone $inicio)->modify('+30 minutes');

  $evento = new Google\Service\Calendar\Event([
    'summary' => 'Cita - Corte de cabello',
    'description' => 'Cliente WhatsApp: ' . $telefono,
    'start' => [
      'dateTime' => $inicio->format(DateTime::RFC3339),
      'timeZone' => 'America/Chicago',
    ],
    'end' => [
      'dateTime' => $fin->format(DateTime::RFC3339),
      'timeZone' => 'America/Chicago',
    ],
  ]);

  return $service->events->insert($calendarId, $evento);
}

function guardarCita($telefono, $fecha, $servicio) {
  $stmt = db()->prepare("
    INSERT INTO citas (telefono_usuario, fecha_inicio, servicio)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$telefono, $fecha, $servicio]);
}


function db() {
    $host = "localhost";
    $port = "3308";
    $dbname = "whatsapp_agenda";
    $user = "root";
    $password = "Barcelona/95";

    try {
        // En el DSN agregamos port=3308
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        
        $pdo = new PDO($dsn, $user, $password);
        
        // Es buena práctica configurar el manejo de errores
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    } catch (PDOException $e) {
        // En un chatbot, es mejor loguear el error que mostrarlo al usuario de WhatsApp
        error_log("Error de conexión: " . $e->getMessage());
        return null;
    }
}

function procesarMensaje($telefono, $mensaje) {

    $intencion = detectarIntencion($mensaje);
    $estado = obtenerEstadoConversacion($telefono);
	
	if ($estado === 'confirmar_fecha') {

	  if (str_contains($mensaje, 'si')) {
			return confirmarReprogramacion($telefono);
		} elseif (str_contains($mensaje, 'no')) {
			return cancelarReprogramacion($telefono);
		} else {
			return "Por favor, confirma con un *Sí* para proceder o un *No* para cancelar la reprogramación. 🙏";
		}
	}

    // SOLO reprogramar si NO es un comando
    if ($estado === 'esperando_fecha' && $intencion === 'desconocido') {
        return reprogramarCita($telefono, $mensaje);
    }

    switch ($intencion) {
        case 'crear':
            return crearCita($telefono, $mensaje);

        case 'cancelar':
            return cancelarCita($telefono);

        case 'reprogramar':
            return pedirNuevaFecha($telefono);
			
        case 'consultar':
            return consultarMiCita($telefono);

        default:
            return "Hola ¿Cómo te puedo ayudar? 😊\n\n" .
                   "Puedes escribir:\n" .
                   "- Crear nueva cita\n" .
                   "- Cancelar mi cita\n" .
                   "- Reprogramar mi cita\n" .
				   "- Consultar mi cita";
    }
}

// ... resto del código legacy ...
*/
