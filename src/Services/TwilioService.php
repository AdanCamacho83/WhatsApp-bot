<?php

namespace App\Services;

use Twilio\Rest\Client;

/**
 * Servicio para manejar comunicación con Twilio/WhatsApp
 */
class TwilioService
{
    private Client $client;
    private string $whatsappNumber;

    public function __construct(array $config)
    {
        $this->client = new Client(
            $config['account_sid'],
            $config['auth_token']
        );
        $this->whatsappNumber = $config['whatsapp_number'];
    }

    /**
     * Envía un mensaje de WhatsApp
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $this->client->messages->create(
                $to,
                [
                    'from' => $this->whatsappNumber,
                    'body' => $message
                ]
            );
            return true;
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            // Detectar límite diario excedido
            if (strpos($errorMsg, '429') !== false || strpos($errorMsg, 'daily messages limit') !== false) {
                error_log("⚠️ LÍMITE DIARIO DE TWILIO ALCANZADO: " . $errorMsg);
            } else {
                error_log("Error al enviar mensaje de WhatsApp: " . $errorMsg);
            }
            
            return false;
        }
    }

    /**
     * Genera respuesta XML para Twilio webhook
     */
    public function generateResponse(string $message): void
    {
        header("Content-Type: text/xml");
        echo "<Response><Message>" . htmlspecialchars($message) . "</Message></Response>";
    }
}
