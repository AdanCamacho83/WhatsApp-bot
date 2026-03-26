<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use DateTime;
use DateTimeZone;

/**
 * Servicio para manejar Google Calendar
 */
class GoogleCalendarService
{
    private Calendar $service;
    private string $calendarId;
    private string $timezone;

    public function __construct(array $config)
    {
        $client = new Client();
        $client->setAuthConfig($config['credentials_path']);
        $client->setScopes([Calendar::CALENDAR]);
        
        $this->service = new Calendar($client);
        $this->calendarId = $config['calendar_id'];
        $this->timezone = $config['timezone'];
    }

    /**
     * Crea un evento de cita en el calendario
     */
    public function createAppointment(string $telefono, string $fechaHora, string $servicio = 'Corte de cabello'): ?Event
    {
        try {
            $inicio = new DateTime($fechaHora, new DateTimeZone($this->timezone));
            $fin = (clone $inicio)->modify('+30 minutes');

            $evento = new Event([
                'summary' => 'Cita - ' . $servicio,
                'description' => 'Cliente WhatsApp: ' . $telefono,
                'start' => [
                    'dateTime' => $inicio->format(DateTime::RFC3339),
                    'timeZone' => $this->timezone,
                ],
                'end' => [
                    'dateTime' => $fin->format(DateTime::RFC3339),
                    'timeZone' => $this->timezone,
                ],
            ]);

            return $this->service->events->insert($this->calendarId, $evento);
        } catch (\Exception $e) {
            error_log("Error al crear evento en Google Calendar: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza un evento existente
     */
    public function updateAppointment(string $eventId, string $fechaHora): bool
    {
        try {
            $event = $this->service->events->get($this->calendarId, $eventId);
            
            $inicio = new DateTime($fechaHora, new DateTimeZone($this->timezone));
            $fin = (clone $inicio)->modify('+30 minutes');

            $event->setStart([
                'dateTime' => $inicio->format(DateTime::RFC3339),
                'timeZone' => $this->timezone,
            ]);
            $event->setEnd([
                'dateTime' => $fin->format(DateTime::RFC3339),
                'timeZone' => $this->timezone,
            ]);

            $this->service->events->update($this->calendarId, $eventId, $event);
            return true;
        } catch (\Exception $e) {
            error_log("Error al actualizar evento en Google Calendar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un evento del calendario
     */
    public function deleteAppointment(string $eventId): bool
    {
        try {
            $this->service->events->delete($this->calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            error_log("Error al eliminar evento en Google Calendar: " . $e->getMessage());
            return false;
        }
    }
}
