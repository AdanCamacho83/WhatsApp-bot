<?php
echo consultarMiCita('whatsapp:+12247757725');

function consultarMiCita($telefono) {
    $db = db();
    
    $stmt = $db->prepare("SELECT fecha_inicio FROM citas WHERE telefono_usuario = ? AND estado = 'activa' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$telefono]);
    $obj = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($obj && isset($obj['fecha_inicio'])) {
        $soloFecha = date('d/m/Y', strtotime($obj['fecha_inicio']));
        $soloHora = date('g:i A', strtotime($obj['fecha_inicio']));
        $fechaLarga = formatearFechaLarga($soloFecha, 'ES');
        return "Tu servicio es el " . $fechaLarga . " a las " . $soloHora . " 😊";
    } else {
        return "No tienes agendada ninguna fecha activa actualmente. 🤔";
    }
}

function formatearFechaLarga($fechaInput, $idioma) {
    // 1. Convertir el formato dd/mm/aaaa a un objeto DateTime
    $fechaObj = DateTime::createFromFormat('d/m/Y', $fechaInput);

    if (!$fechaObj) {
        return "Formato de fecha inválido";
    }

    // 2. Diccionario de traducciones
    $traducciones = [
        'ES' => [
            'dias' => ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
            'meses' => [
                1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
            ],
            'formato' => "%s %s de %s de %s" // Ejemplo: jueves 21 de mayo de 2026
        ],
        'EN' => [
            'dias' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'meses' => [
                1 => 'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ],
            'formato' => "%s, %s %s, %s" // Ejemplo: thursday, may 21, 2026
        ]
    ];

    // 3. Extraer las partes numéricas de la fecha
    $numDiaSemana = $fechaObj->format('w'); // 0 (domingo) a 6 (sábado)
    $diaMes       = $fechaObj->format('j'); // 1 a 31
    $numMes       = $fechaObj->format('n'); // 1 a 12
    $anio         = $fechaObj->format('Y'); // 2026

    // 4. Construir la cadena según el idioma
    $lang = ($idioma === 'EN') ? 'EN' : 'ES';
    $d = $traducciones[$lang]['dias'][$numDiaSemana];
    $m = $traducciones[$lang]['meses'][$numMes];

    if ($lang === 'ES') {
        // Formato: jueves 21 de mayo de 2026
        return sprintf($traducciones['ES']['formato'], $d, $diaMes, $m, $anio);
    } else {
        // Formato: thursday, may 21, 2026
        return sprintf($traducciones['EN']['formato'], $d, $m, $diaMes, $anio);
    }
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
?>

