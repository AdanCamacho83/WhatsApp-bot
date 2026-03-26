<?php

namespace App\Utils;

class SessionManager
{
    // Tiempo de inactividad antes de expirar la sesión (30 minutos por defecto)
    private static int $sessionTimeout = 1800; // 30 minutos en segundos

    /**
     * Iniciar sesión con configuración segura
     */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración segura de sesión
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            
            session_start();
        }
    }

    /**
     * Verificar si hay una sesión activa y válida
     * 
     * @return bool True si la sesión es válida, false en caso contrario
     */
    public static function verificarSesion(): bool
    {
        self::iniciar();

        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }

        // Verificar tiempo de inactividad
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            
            if ($inactiveTime > self::$sessionTimeout) {
                self::destruir();
                return false;
            }
        }

        // Verificar IP del cliente (seguridad adicional)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            error_log("Intento de secuestro de sesión detectado. IP original: " . $_SESSION['ip_address'] . ", IP actual: " . $_SERVER['REMOTE_ADDR']);
            self::destruir();
            return false;
        }

        // Actualizar tiempo de última actividad
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Requerir sesión activa o redireccionar al login
     */
    public static function requerirSesion(): void
    {
        if (!self::verificarSesion()) {
            header('Location: login.php?sesion=expirada');
            exit;
        }
    }

    /**
     * Obtener información del usuario de la sesión
     * 
     * @return array|null Datos del usuario o null si no hay sesión
     */
    public static function obtenerUsuario(): ?array
    {
        self::iniciar();

        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'usuario' => $_SESSION['usuario_nombre'] ?? null,
            'empresa' => $_SESSION['empresa_nombre'] ?? null,
            'telefono' => $_SESSION['empresa_telefono'] ?? null
        ];
    }

    /**
     * Destruir la sesión completamente
     */
    public static function destruir(): void
    {
        self::iniciar();
        
        $_SESSION = array();
        
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
        
        session_destroy();
    }

    /**
     * Establecer el tiempo de inactividad de la sesión
     * 
     * @param int $segundos Tiempo en segundos
     */
    public static function setSessionTimeout(int $segundos): void
    {
        self::$sessionTimeout = $segundos;
    }

    /**
     * Obtener el tiempo de inactividad de la sesión
     * 
     * @return int Tiempo en segundos
     */
    public static function getSessionTimeout(): int
    {
        return self::$sessionTimeout;
    }
}
