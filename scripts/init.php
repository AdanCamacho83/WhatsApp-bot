<?php

/**
 * Script de inicialización del proyecto
 * Ejecuta: php scripts/init.php
 */

echo "=================================\n";
echo "WhatsApp Bot - Inicialización\n";
echo "=================================\n\n";

// 1. Verificar que composer está instalado
echo "[1/5] Verificando Composer...\n";
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "❌ Error: Ejecuta 'composer install' primero\n";
    exit(1);
}
echo "✓ Composer OK\n\n";

// 2. Crear carpeta de logs si no existe
echo "[2/5] Creando carpeta de logs...\n";
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "✓ Carpeta 'logs' creada\n\n";
} else {
    echo "✓ Carpeta 'logs' ya existe\n\n";
}

// 3. Verificar archivo de configuración
echo "[3/5] Verificando configuración...\n";
$configFile = __DIR__ . '/../config/config.php';
$configExample = __DIR__ . '/../config/config.example.php';

if (!file_exists($configFile)) {
    echo "⚠️  Advertencia: config.php no existe\n";
    echo "   Copiando desde config.example.php...\n";
    copy($configExample, $configFile);
    echo "✓ config.php creado\n";
    echo "   ⚠️  IMPORTANTE: Edita config/config.php con tus credenciales\n\n";
} else {
    echo "✓ config.php existe\n\n";
}

// 4. Verificar credenciales de Google Calendar
echo "[4/5] Verificando credenciales de Google Calendar...\n";
$calendarKey = __DIR__ . '/../keys/calendar.json';
if (!file_exists($calendarKey)) {
    echo "⚠️  Advertencia: keys/calendar.json no existe\n";
    echo "   Descarga tus credenciales de Google Cloud Console\n\n";
} else {
    echo "✓ calendar.json encontrado\n\n";
}

// 5. Generar autoload
echo "[5/5] Generando autoload...\n";
exec('composer dump-autoload', $output, $return);
if ($return === 0) {
    echo "✓ Autoload generado\n\n";
} else {
    echo "❌ Error al generar autoload\n\n";
}

echo "=================================\n";
echo "Inicialización completada\n";
echo "=================================\n\n";

echo "Próximos pasos:\n";
echo "1. Edita config/config.php con tus credenciales\n";
echo "2. Coloca calendar.json en la carpeta keys/\n";
echo "3. Configura el webhook en Twilio: https://tu-dominio.com/public/index.php\n";
echo "4. Configura el cronjob para recordatorios\n\n";

echo "Para más información, consulta README.md\n";
