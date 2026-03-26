<?php

namespace App\Models;

use App\Database\Database;
use PDO;

/**
 * Modelo para manejar las citas
 */
class Cita
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Crea una nueva cita
     */
    public function crear(string $telefono, string $fecha, string $servicio, int $idCliente): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO citas (telefono_usuario, fecha_inicio, servicio, idCliente)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$telefono, $fecha, $servicio, $idCliente]);
        } catch (\Exception $e) {
            error_log("Error al crear cita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la cita activa de un usuario
     */
    public function obtenerActiva(string $telefono, ?int $idCliente = null): ?array
    {
        if ($idCliente !== null) {
            $stmt = $this->db->prepare("
                SELECT * FROM citas 
                WHERE telefono_usuario = ? 
                AND estado = 'activa'
                AND idCliente = ?
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$telefono, $idCliente]);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM citas 
                WHERE telefono_usuario = ? 
                AND estado = 'activa' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$telefono]);
        }
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Obtiene todas las citas activas
     */
    public function obtenerTodas(array $estados = ['activa'], ?int $idCliente = null): array
    {
        $placeholders = str_repeat('?,', count($estados) - 1) . '?';
        
        if ($idCliente !== null) {
            $stmt = $this->db->prepare("
                SELECT * FROM citas 
                WHERE estado IN ($placeholders)
                AND idCliente = ?
                ORDER BY fecha_inicio ASC
            ");
            $params = array_merge($estados, [$idCliente]);
            $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM citas 
                WHERE estado IN ($placeholders)
                ORDER BY fecha_inicio ASC
            ");
            $stmt->execute($estados);
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Obtiene citas en un rango de fechas
     * Si se proporciona idEmpresa, filtra por citas de clientes de esa empresa
     */
    public function obtenerPorRango(string $desde, string $hasta, ?int $idEmpresa = null): array
    {
        if ($idEmpresa !== null) {
            $stmt = $this->db->prepare("
                SELECT c.id, c.fecha_inicio, c.servicio, c.estado, c.telefono_usuario
                FROM citas c
                INNER JOIN clientes cl ON c.idCliente = cl.id
                WHERE c.fecha_inicio >= ?
                AND cl.idEmpresa = ?
            ");
            $stmt->execute([$desde, $idEmpresa]);
        } else {
            $stmt = $this->db->prepare("
                SELECT id, fecha_inicio, servicio, estado, telefono_usuario
                FROM citas
                WHERE fecha_inicio >= ?
            ");
            $stmt->execute([$desde]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Actualiza la fecha de una cita
     */
    public function actualizar(int $id, string $nuevaFecha): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET fecha_inicio = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$nuevaFecha, $id]);
        } catch (\Exception $e) {
            error_log("Error al actualizar cita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el servicio de una cita
     */
    public function actualizarServicio(int $id, string $servicio): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET servicio = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$servicio, $id]);
        } catch (\Exception $e) {
            error_log("Error al actualizar servicio de cita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reprograma la cita activa de un usuario
     */
    public function reprogramar(string $telefono, string $nuevaFecha): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET fecha_inicio = ?, recordatorio_enviado = 0
                WHERE telefono_usuario = ? 
                AND estado = 'activa' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            return $stmt->execute([$nuevaFecha, $telefono]);
        } catch (\Exception $e) {
            error_log("Error al reprogramar cita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancela la cita activa de un usuario
     */
    public function cancelar(string $telefono): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET estado = 'cancelada' 
                WHERE telefono_usuario = ? 
                AND estado = 'activa' 
                LIMIT 1
            ");
            $stmt->execute([$telefono]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("Error al cancelar cita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene citas pendientes de recordatorio
     */
    public function obtenerPendientesRecordatorio(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM citas
            WHERE recordatorio_enviado = 0
            AND estado = 'activa'
            AND fecha_inicio BETWEEN ? AND ?
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * Marca una cita como recordatorio enviado
     */
    public function marcarRecordatorioEnviado(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET recordatorio_enviado = 1 
                WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            error_log("Error al marcar recordatorio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene una cita por ID
     * Si se proporciona idEmpresa, verifica que la cita pertenezca a un cliente de esa empresa
     */
    public function obtenerPorId(int $id, ?int $idEmpresa = null): ?array
    {
        if ($idEmpresa !== null) {
            $stmt = $this->db->prepare("
                SELECT c.* FROM citas c
                INNER JOIN clientes cl ON c.idCliente = cl.id
                WHERE c.id = ? AND cl.idEmpresa = ?
            ");
            $stmt->execute([$id, $idEmpresa]);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM citas WHERE id = ?
            ");
            $stmt->execute([$id]);
        }
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
}
