<?php

namespace App\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Services\TwilioService;
use App\Services\GoogleCalendarService;
use App\Services\OpenAIService;
use App\Helpers\DateHelper;
use App\Utils\EmpresaCodeManager;
use DateTime;
use DateTimeZone;

/**
 * Controlador principal para manejar las conversaciones de WhatsApp
 */
class WhatsAppController
{
    private Cita $citaModel;
    private Cliente $clienteModel;
    private Conversacion $conversacionModel;
    private TwilioService $twilioService;
    private GoogleCalendarService $calendarService;
    private OpenAIService $openAIService;
    private array $config;
    private ?int $idCliente = null; // ID del cliente actual en la tabla clientes

    public function __construct(
        TwilioService $twilioService,
        GoogleCalendarService $calendarService,
        OpenAIService $openAIService,
        array $config
    ) {
        $this->citaModel = new Cita();
        $this->clienteModel = new Cliente();
        $this->conversacionModel = new Conversacion();
        $this->twilioService = $twilioService;
        $this->calendarService = $calendarService;
        $this->openAIService = $openAIService;
        $this->config = $config;
    }

    /**
     * Procesa el mensaje entrante y retorna la respuesta
     */
    public function procesarMensaje(string $telefono, string $mensaje): string
    {
        // Verificar si el cliente ya está registrado
        $clienteData = $this->clienteModel->obtenerEmpresaPorTelefono($telefono);
        
        if (!$clienteData) {
            // Cliente no registrado - verificar si envió código de empresa
            $idEmpresaRegistrada = EmpresaCodeManager::registrarClienteConCodigo($telefono, $mensaje);
            
            if ($idEmpresaRegistrada) {
                // Cliente registrado exitosamente con código
                // Obtener datos del cliente para guardar su ID
                $clienteData = $this->clienteModel->obtenerEmpresaPorTelefono($telefono);
                $this->idCliente = $clienteData['id'];
                
                return "✅ *¡Registro exitoso!*\n\n"
                     . "Has sido registrado en: *{$clienteData['nombre_empresa']}*\n\n"
                     . "Ahora puedes usar todos nuestros servicios:\n"
                     . "• Agendar citas\n"
                     . "• Cancelar citas\n"
                     . "• Reprogramar citas\n"
                     . "• Ver tus citas\n\n"
                     . "¿En qué te puedo ayudar? 😊";
            } else {
                // No envió código o código inválido
                return EmpresaCodeManager::getMensajeSinCodigo();
            }
        }
        
        // Cliente ya registrado - guardar idCliente
        $this->idCliente = $clienteData['id'];
        
        $intencion = $this->detectarIntencion($mensaje);
        $estado = $this->conversacionModel->obtenerEstado($telefono);

        // Si está esperando confirmación de fecha para reprogramación
        if ($estado === 'confirmar_fecha') {
            if (str_contains($mensaje, 'si')) {
                return $this->confirmarReprogramacion($telefono);
            } elseif (str_contains($mensaje, 'no')) {
                return $this->cancelarReprogramacion($telefono);
            } else {
                return "Por favor, confirma con un *Sí* para proceder o un *No* para cancelar la reprogramación. 🙏";
            }
        }

        // Si está esperando confirmación de fecha para nueva cita
        if ($estado === 'confirmar_fecha_nueva_cita') {
            if (str_contains($mensaje, 'si')) {
                return $this->confirmarNuevaCita($telefono);
            } elseif (str_contains($mensaje, 'no')) {
                return $this->cancelarNuevaCita($telefono);
            } else {
                return "Por favor, confirma con un *Sí* para agendar la cita o un *No* para cancelar. 🙏";
            }
        }

        // Si está esperando fecha para reprogramación y no es un comando
        if ($estado === 'esperando_fecha' && $intencion === 'desconocido') {
            return $this->reprogramarCita($telefono, $mensaje);
        }

        // Si está esperando fecha para nueva cita y no es un comando
        if ($estado === 'esperando_fecha_nueva_cita' && $intencion === 'desconocido') {
            return $this->procesarFechaNuevaCita($telefono, $mensaje);
        }

        // Si está esperando servicio para nueva cita y no es un comando
        if ($estado === 'esperando_servicio_nueva_cita' && $intencion === 'desconocido') {
            return $this->procesarServicioNuevaCita($telefono, $mensaje);
        }

        // Si está esperando servicio para reprogramación y no es un comando
        if ($estado === 'esperando_servicio_reprogramar' && $intencion === 'desconocido') {
            return $this->procesarServicioReprogramar($telefono, $mensaje);
        }

        // Procesar según la intención
        switch ($intencion) {
            case 'crear':
                return $this->crearCita($telefono, $mensaje);

            case 'cancelar':
                return $this->cancelarCita($telefono);

            case 'reprogramar':
                return $this->pedirNuevaFecha($telefono);

            case 'consultar':
                return $this->consultarMiCita($telefono);

            default:
                return "Hola ¿Cómo te puedo ayudar? 😊\n\n" .
                       "Puedes escribir:\n" .
                       "- Crear nueva cita\n" .
                       "- Cancelar mi cita\n" .
                       "- Reprogramar mi cita\n" .
                       "- Consultar mi cita";
        }
    }

    /**
     * Inicia el proceso de creación de cita solicitando el servicio
     */
    private function crearCita(string $telefono, string $mensaje): string
    {
        // Actualizar estado para esperar servicio
        $this->conversacionModel->actualizarEstado($telefono, 'esperando_servicio_nueva_cita');

        return "💈 ¡Perfecto! Vamos a agendar tu cita.\n\n" .
               "¿Qué servicio deseas?\n\n" .
               "Ejemplos:\n" .
               "👉 Corte de cabello\n" .
               "👉 Corte y barba\n" .
               "👉 Tinte\n" .
               "👉 Peinado";
    }

    /**
     * Procesa el servicio proporcionado y solicita fecha/hora
     */
    private function procesarServicioNuevaCita(string $telefono, string $mensaje): string
    {
        // Guardar servicio y cambiar a esperar fecha
        $servicio = ucfirst(trim($mensaje));
        $this->conversacionModel->guardarServicioYSolicitarFecha($telefono, $servicio, 'esperando_fecha_nueva_cita');

        return "📅 Perfecto, servicio: *{$servicio}*\n\n" .
               "¿Para qué día y hora deseas tu cita?\n\n" .
               "Ejemplos:\n" .
               "👉 Mañana a las 5 pm\n" .
               "👉 El viernes a las 10 am\n" .
               "👉 15 de enero a las 3 pm";
    }

    /**
     * Procesa la fecha proporcionada por el usuario para nueva cita
     */
    private function procesarFechaNuevaCita(string $telefono, string $mensaje): string
    {
        $resultado = $this->openAIService->parseDateTime($mensaje, $this->config['logs']['ia_errors']);

        if (!$resultado) {
            return "❌ No pude entender la fecha 😕\n\n" .
                   "Intenta algo como:\n" .
                   "👉 Mañana a las 5 pm\n" .
                   "👉 El jueves a las 10 am";
        }

        $fechaHora = $resultado['fecha'] . ' ' . $resultado['hora'];
        
        // Guardar fecha propuesta y cambiar estado en una sola operación
        $this->conversacionModel->guardarFechaPropuestaNuevaCita($telefono, $fechaHora);

        return "📅 ¿Confirmas esta fecha para tu cita?\n\n" .
               "🗓️ " . date('d/m/Y g:i A', strtotime($fechaHora)) . "\n\n" .
               "Responde:\n" .
               "✅ Sí\n" .
               "❌ No";
    }

    /**
     * Confirma y crea la nueva cita
     */
    private function confirmarNuevaCita(string $telefono): string
    {
        $conv = $this->conversacionModel->obtener($telefono);

        if (!$conv || !isset($conv['fecha_propuesta'])) {
            return "❌ No encontré una fecha propuesta para tu cita.";
        }

        $fechaHora = $conv['fecha_propuesta'];
        $servicio = $conv['servicio_propuesto'] ?? 'Corte de cabello'; // Usar servicio guardado o default

        try {
            // Crear en Google Calendar solo si está activado en la configuración
            if (isset($this->config['google_calendar']['activo']) && $this->config['google_calendar']['activo'] === true) {
                $this->calendarService->createAppointment($telefono, $fechaHora, $servicio);
            }

            // Guardar en base de datos con idCliente
            if (!$this->idCliente) {
                throw new \Exception("No se pudo identificar el cliente");
            }
            $this->citaModel->crear($telefono, $fechaHora, $servicio, $this->idCliente);

            // Limpiar conversación
            $this->conversacionModel->eliminar($telefono);

            $fechaFormateada = date('d/m/Y', strtotime($fechaHora));
            $horaFormateada = date('g:i A', strtotime($fechaHora));

            return "✅ ¡Listo! Tu cita fue agendada exitosamente 🙌\n\n" .
                   "📅 Fecha: " . $fechaFormateada . "\n" .
                   "⏰ Hora: " . $horaFormateada . "\n" .
                   "💈 Servicio: " . $servicio . "\n\n" .
                   "Te enviaré un recordatorio 24 horas antes. ¡Nos vemos! 😊";
        } catch (\Exception $e) {
            $mensajeError = "[" . date('Y-m-d H:i:s') . "] Error al crear cita: " . $e->getMessage() . PHP_EOL;
            file_put_contents($this->config['logs']['errors'], $mensajeError, FILE_APPEND | LOCK_EX);
            
            return "❌ Hubo un error al agendar tu cita. Por favor intenta más tarde.";
        }
    }

    /**
     * Cancela el proceso de creación de nueva cita
     */
    private function cancelarNuevaCita(string $telefono): string
    {
        $this->conversacionModel->eliminar($telefono);
        return "Entendido, no he agendado ninguna cita. ¿Hay algo más en lo que pueda ayudarte? 😊";
    }

    /**
     * Consulta la cita activa del usuario
     */
    private function consultarMiCita(string $telefono): string
    {
        $cita = $this->citaModel->obtenerActiva($telefono);

        if ($cita && isset($cita['fecha_inicio'])) {
            $soloFecha = date('d/m/Y', strtotime($cita['fecha_inicio']));
            $soloHora = date('g:i A', strtotime($cita['fecha_inicio']));
            $fechaLarga = DateHelper::formatearFechaLarga($soloFecha, 'ES');
            $servicio = $cita['servicio'] ?? 'Servicio';
            
            return "Tu cita está agendada para el " . $fechaLarga . " a las " . $soloHora . " 😊\n\n" .
                   "💈 Servicio: " . $servicio;
        } else {
            return "No tienes registrada ninguna cita. 🤔";
        }
    }

    /**
     * Pide al usuario el servicio para reprogramar
     */
    private function pedirNuevaFecha(string $telefono): string
    {
        $this->conversacionModel->actualizarEstado($telefono, 'esperando_servicio_reprogramar');

        return "🔁 Claro 😊\n\n" .
               "¿Qué servicio deseas para tu nueva cita?\n\n" .
               "Ejemplos:\n" .
               "👉 Corte de cabello\n" .
               "👉 Corte y barba\n" .
               "👉 Tinte\n" .
               "👉 Peinado";
    }

    /**
     * Procesa el servicio para reprogramación y solicita fecha
     */
    private function procesarServicioReprogramar(string $telefono, string $mensaje): string
    {
        // Guardar servicio y cambiar a esperar fecha
        $servicio = ucfirst(trim($mensaje));
        $this->conversacionModel->guardarServicioYSolicitarFecha($telefono, $servicio, 'esperando_fecha');

        return "📅 Perfecto, servicio: *{$servicio}*\n\n" .
               "¿Para qué día y hora deseas reprogramar tu cita?\n\n" .
               "Ejemplos:\n" .
               "👉 Mañana a las 5 pm\n" .
               "👉 El jueves a las 10 am";
    }

    /**
     * Procesa la nueva fecha para reprogramar
     */
    private function reprogramarCita(string $telefono, string $mensaje): string
    {
        $resultado = $this->openAIService->parseDateTime($mensaje, $this->config['logs']['ia_errors']);

        if (!$resultado) {
            return "❌ No pude entender la fecha 😕\n\n" .
                   "Intenta algo como:\n" .
                   "👉 Mañana a las 5 pm\n" .
                   "👉 El jueves a las 10 am";
        }

        $fechaHora = $resultado['fecha'] . ' ' . $resultado['hora'];
        $this->conversacionModel->guardarFechaPropuesta($telefono, $fechaHora);

        return "🔁 ¿Confirmas esta nueva fecha?\n\n" .
               "📅 " . date('d/m/Y g:i A', strtotime($fechaHora)) . "\n\n" .
               "Responde:\n" .
               "✅ Sí\n" .
               "❌ No";
    }

    /**
     * Confirma la reprogramación de la cita
     */
    private function confirmarReprogramacion(string $telefono): string
    {
        $conv = $this->conversacionModel->obtener($telefono);

        if (!$conv || !isset($conv['fecha_propuesta'])) {
            return "❌ No encontré una fecha propuesta para reprogramar.";
        }

        // Reprogramar fecha
        $this->citaModel->reprogramar($telefono, $conv['fecha_propuesta']);
        
        // Si hay servicio propuesto, actualizar también el servicio
        if (isset($conv['servicio_propuesto']) && !empty($conv['servicio_propuesto'])) {
            // Obtener la cita activa y actualizar servicio
            $cita = $this->citaModel->obtenerActiva($telefono);
            if ($cita) {
                $this->citaModel->actualizarServicio($cita['id'], $conv['servicio_propuesto']);
            }
        }
        
        $this->conversacionModel->eliminar($telefono);

        return "✅ ¡Listo! Tu cita fue reprogramada exitosamente 🙌";
    }

    /**
     * Cancela el proceso de reprogramación
     */
    private function cancelarReprogramacion(string $telefono): string
    {
        $this->conversacionModel->eliminar($telefono);
        return "Entendido, no he realizado ningún cambio. ¿Hay algo más en lo que pueda ayudarte? 😊";
    }

    /**
     * Cancela la cita activa del usuario
     */
    private function cancelarCita(string $telefono): string
    {
        if ($this->citaModel->cancelar($telefono)) {
            return "❌ Tu cita ha sido cancelada correctamente.\n\nSi deseas agendar una nueva, solo escríbeme 😊";
        } else {
            return "No encontré ninguna cita activa para cancelar 🤔";
        }
    }

    /**
     * Detecta la intención del mensaje
     */
    private function detectarIntencion(string $mensaje): string
    {
        if (str_contains($mensaje, 'crear') || str_contains($mensaje, 'nueva')) {
            return 'crear';
        }

        if (str_contains($mensaje, 'cancel') || str_contains($mensaje, 'eliminar')) {
            return 'cancelar';
        }

        if (str_contains($mensaje, 'reprogr') || str_contains($mensaje, 'cambiar')) {
            return 'reprogramar';
        }

        if (str_contains($mensaje, 'consultar') || str_contains($mensaje, 'verificar')) {
            return 'consultar';
        }

        return 'desconocido';
    }
}
