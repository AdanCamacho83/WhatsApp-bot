<?php

namespace App\Models;

use App\Database\Database;
use PDO;

/**
 * Modelo para manejar las conversaciones
 */
class Conversacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene la conversación de un usuario
     */
    public function obtener(string $telefono): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM conversaciones 
            WHERE telefono = ? 
            LIMIT 1
        ");
        $stmt->execute([$telefono]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Obtiene el estado actual de la conversación
     */
    public function obtenerEstado(string $telefono): string
    {
        $conversacion = $this->obtener($telefono);
        return $conversacion ? $conversacion['estado'] : 'nuevo';
    }

    /**
     * Crea o actualiza el estado de una conversación
     */
    public function actualizarEstado(string $telefono, string $estado): bool
    {
        try {
            $stmt = $this->db->prepare("
                REPLACE INTO conversaciones (telefono, estado) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$telefono, $estado]);
        } catch (\Exception $e) {
            error_log("Error al actualizar estado de conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda una fecha propuesta para reprogramación
     */
    public function guardarFechaPropuesta(string $telefono, string $fechaHora): bool
    {
        try {
            // Usar REPLACE INTO para insertar si no existe o actualizar si existe
            $stmt = $this->db->prepare("
                REPLACE INTO conversaciones (telefono, fecha_propuesta, estado)
                VALUES (?, ?, 'confirmar_fecha')
            ");
            return $stmt->execute([$telefono, $fechaHora]);
        } catch (\Exception $e) {
            error_log("Error al guardar fecha propuesta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda una fecha propuesta para nueva cita
     */
    public function guardarFechaPropuestaNuevaCita(string $telefono, string $fechaHora): bool
    {
        try {
            // Usar UPDATE para no perder el servicio_propuesto
            $stmt = $this->db->prepare("
                UPDATE conversaciones
                SET fecha_propuesta = ?, estado = 'confirmar_fecha_nueva_cita'
                WHERE telefono = ?
            ");
            $resultado = $stmt->execute([$fechaHora, $telefono]);
            
            // Si no existe el registro, usar REPLACE INTO con todos los campos
            if ($stmt->rowCount() === 0) {
                $stmt = $this->db->prepare("
                    REPLACE INTO conversaciones (telefono, fecha_propuesta, estado)
                    VALUES (?, ?, 'confirmar_fecha_nueva_cita')
                ");
                $resultado = $stmt->execute([$telefono, $fechaHora]);
            }
            
            return $resultado;
        } catch (\Exception $e) {
            error_log("Error al guardar fecha propuesta para nueva cita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda el servicio propuesto en la conversación
     */
    public function guardarServicio(string $telefono, string $servicio): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversaciones
                SET servicio_propuesto = ?
                WHERE telefono = ?
            ");
            $resultado = $stmt->execute([$servicio, $telefono]);
            
            // Si no existe el registro, crearlo
            if ($stmt->rowCount() === 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO conversaciones (telefono, servicio_propuesto)
                    VALUES (?, ?)
                ");
                $resultado = $stmt->execute([$telefono, $servicio]);
            }
            
            return $resultado;
        } catch (\Exception $e) {
            error_log("Error al guardar servicio propuesto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda servicio y actualiza estado para solicitar fecha
     */
    public function guardarServicioYSolicitarFecha(string $telefono, string $servicio, string $estadoSiguiente): bool
    {
        try {
            // Verificar si existe el registro
            $conv = $this->obtener($telefono);
            
            if ($conv) {
                // Actualizar servicio y estado
                $stmt = $this->db->prepare("
                    UPDATE conversaciones
                    SET servicio_propuesto = ?, estado = ?
                    WHERE telefono = ?
                ");
                return $stmt->execute([$servicio, $estadoSiguiente, $telefono]);
            } else {
                // Insertar nuevo registro
                $stmt = $this->db->prepare("
                    INSERT INTO conversaciones (telefono, servicio_propuesto, estado)
                    VALUES (?, ?, ?)
                ");
                return $stmt->execute([$telefono, $servicio, $estadoSiguiente]);
            }
        } catch (\Exception $e) {
            error_log("Error al guardar servicio y actualizar estado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una conversación
     */
    public function eliminar(string $telefono): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM conversaciones 
                WHERE telefono = ?
            ");
            return $stmt->execute([$telefono]);
        } catch (\Exception $e) {
            error_log("Error al eliminar conversación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpia conversaciones antiguas
     */
    public function limpiarAntiguas(int $diasAntiguedad = 7): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM conversaciones 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$diasAntiguedad]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("Error al limpiar conversaciones antiguas: " . $e->getMessage());
            return 0;
        }
    }
}
