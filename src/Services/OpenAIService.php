<?php

namespace App\Services;

/**
 * Servicio para interactuar con OpenAI
 */
class OpenAIService
{
    private string $apiKey;
    private string $model;
    private float $temperature;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->temperature = $config['temperature'];
    }

    /**
     * Envía un prompt a OpenAI y obtiene la respuesta
     */
    public function chat(string $prompt): ?string
    {
        $data = [
            "model" => $this->model,
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => $this->temperature
        ];

        $ch = curl_init("https://api.openai.com/v1/chat/completions");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$this->apiKey}"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return null;
        }

        $json = json_decode($result, true);
        return $json['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Interpreta fecha y hora desde lenguaje natural
     */
    public function parseDateTime(string $mensaje, string $logPath): ?array
    {
        $hoy = date('Y-m-d');

        $prompt = "
        Interpreta la fecha y hora del siguiente texto del usuario.
        
        Texto: \"$mensaje\"
        
        Fecha actual: $hoy
        Zona horaria: America/Chicago
        
        Reglas:
        - Responde SOLO en JSON
        - Usa formato 24 horas
        - Si no hay hora, usa 09:00
        - Si no se puede interpretar, responde {\"error\":\"fecha_invalida\"}
        
        Formato esperado:
        {
          \"fecha\": \"YYYY-MM-DD\",
          \"hora\": \"HH:MM\"
        }";

        try {
            $response = $this->chat($prompt);

            if (!$response) {
                throw new \Exception("La respuesta de OpenAI está vacía.");
            }

            // Limpiar respuesta de markdown
            $response = str_replace(['```json', '```'], '', $response);
            $response = trim($response);

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Error al decodificar JSON: " . json_last_error_msg() . " | Respuesta: " . $response);
            }

            if (isset($data['error'])) {
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            $mensajeError = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . PHP_EOL;
            file_put_contents($logPath, $mensajeError, FILE_APPEND | LOCK_EX);
            return null;
        }
    }
}
