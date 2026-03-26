<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Clase singleton para manejar la conexión a la base de datos
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config;

    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {}

    /**
     * Inicializa la configuración de la base de datos
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Obtiene la instancia única de la conexión PDO
     */
    public static function getConnection(): ?PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    self::$config['host'],
                    self::$config['port'],
                    self::$config['dbname'],
                    self::$config['charset']
                );

                self::$instance = new PDO(
                    $dsn,
                    self::$config['user'],
                    self::$config['password']
                );

                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Establecer zona horaria UTC en MySQL
                self::$instance->exec("SET time_zone = '+00:00'");

            } catch (PDOException $e) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                return null;
            }
        }

        return self::$instance;
    }

    /**
     * Cierra la conexión (útil para testing)
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}
