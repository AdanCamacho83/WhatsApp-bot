<?php
session_start();

// Registrar el cierre de sesión
if (isset($_SESSION['usuario_nombre'])) {
    error_log("Cierre de sesión para usuario: " . $_SESSION['usuario_nombre'] . " desde IP: " . $_SERVER['REMOTE_ADDR']);
}

// Eliminar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destruir la sesión
session_destroy();

// Redireccionar al login
header('Location: login.php?logout=success');
exit;
