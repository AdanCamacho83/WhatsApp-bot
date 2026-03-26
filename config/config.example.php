<?php

/**
 * Configuración centralizada de la aplicación
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo como config.php
 * 2. Completa tus credenciales reales
 * 3. NO subas config.php a Git (está en .gitignore)
 */
return [
    // Base de datos
    'database' => [
        'host' => 'localhost',
        'port' => '3308',  // Cambia según tu configuración
        'dbname' => 'whatsapp_agenda',
        'user' => 'root',
        'password' => '',  // TU CONTRASEÑA AQUÍ
        'charset' => 'utf8mb4'
    ],

    // Twilio
    'twilio' => [
        'account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'auth_token' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'whatsapp_number' => 'whatsapp:+14155238886'
    ],

    // Google Calendar
    'google_calendar' => [
        'credentials_path' => __DIR__ . '/../keys/calendar.json',
        'calendar_id' => 'tu-calendar-id@group.calendar.google.com',
        'timezone' => 'America/Chicago',
        'activo' => false  // true = guardar en Google Calendar, false = solo en base de datos
    ],

    // OpenAI
    'openai' => [
        'api_key' => 'sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'model' => 'gpt-4.1-mini',
        'temperature' => 0
    ],

    // Zona horaria general
    'timezone' => 'America/Chicago',

    // Logs
    'logs' => [
        'errors' => __DIR__ . '/../logs/errores_citas.log',
        'ia_errors' => __DIR__ . '/../logs/errores_ia.log',
        'reminders' => __DIR__ . '/../logs/recordatorios.log'
    ]
];
