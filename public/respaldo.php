<?php

$from = $_POST['From'] ?? '';
$body = $_POST['Body'] ?? '';

file_put_contents(
  __DIR__ . '/../logs/mensajes.log',
  date('Y-m-d H:i:s') . " | $from | $body\n",
  FILE_APPEND
);

$mensaje = strtolower($body);

if (str_contains($mensaje, 'cita')) {
  $respuesta = "📅 Claro, ¿para qué día deseas la cita?";
} elseif (str_contains($mensaje, 'info')) {
  $respuesta = "💈 Estamos abiertos de 9 AM a 7 PM.";
} elseif (str_contains($mensaje, 'coger')) {
  $respuesta = "¿Quieres culear?";
  } elseif (str_contains($mensaje, 'si')) {
  $respuesta = "Te veo atrás, *te daré por el culo*";
} else {
  $respuesta = "👋 ¿Deseas agendar una cita o recibir información?";
}


// Respuesta a WhatsApp
header("Content-Type: text/xml");

echo "<Response>
        <Message>$respuesta</Message>
      </Response>";