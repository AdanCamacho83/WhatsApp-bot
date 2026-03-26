<?php

namespace App\Models;

use App\Database\Database;
use PDO;
use Exception;

class Cliente
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Registrar un nuevo cliente para una empresa
     */
    public function registrar(int $idEmpresa, string $telefono, string $nombreCliente = ''): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO clientes (idEmpresa, telefono_usuario, nombre_cliente)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    nombre_cliente = IF(VALUES(nombre_cliente) != '', VALUES(nombre_cliente), nombre_cliente),
                    activo = 1,
                    updated_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$idEmpresa, $telefono, $nombreCliente]);
        } catch (Exception $e) {
            error_log("Error al registrar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener la empresa a la que pertenece un teléfono
     */
    public function obtenerEmpresaPorTelefono(string $telefono): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    e.nombre_empresa,
                    e.telefono_contacto as empresa_telefono
                FROM clientes c
                INNER JOIN empresas e ON c.idEmpresa = e.id
                WHERE c.telefono_usuario = ?
                AND c.activo = 1
                LIMIT 1
            ");
            $stmt->execute([$telefono]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error al obtener empresa por teléfono: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si un cliente está registrado
     */
    public function existe(string $telefono): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM clientes 
                WHERE telefono_usuario = ? 
                AND activo = 1
            ");
            $stmt->execute([$telefono]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar existencia de cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los clientes de una empresa
     */
    public function obtenerPorEmpresa(int $idEmpresa, bool $soloActivos = true): array
    {
        try {
            $sql = "
                SELECT * FROM clientes 
                WHERE idEmpresa = ?
            ";
            
            if ($soloActivos) {
                $sql .= " AND activo = 1";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idEmpresa]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener clientes por empresa: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un cliente por ID
     */
    public function obtenerPorId(int $id, ?int $idEmpresa = null): ?array
    {
        try {
            if ($idEmpresa !== null) {
                $stmt = $this->db->prepare("
                    SELECT * FROM clientes 
                    WHERE id = ? AND idEmpresa = ?
                ");
                $stmt->execute([$id, $idEmpresa]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT * FROM clientes WHERE id = ?
                ");
                $stmt->execute([$id]);
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error al obtener cliente por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar información de un cliente
     */
    public function actualizar(int $id, string $nombreCliente = '', string $email = ''): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET nombre_cliente = ?,
                    email = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            return $stmt->execute([$nombreCliente, $email, $id]);
        } catch (Exception $e) {
            error_log("Error al actualizar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactivar un cliente (soft delete)
     */
    public function desactivar(int $id, int $idEmpresa): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET activo = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND idEmpresa = ?
            ");
            return $stmt->execute([$id, $idEmpresa]);
        } catch (Exception $e) {
            error_log("Error al desactivar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactivar un cliente
     */
    public function activar(int $id, int $idEmpresa): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE clientes 
                SET activo = 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND idEmpresa = ?
            ");
            return $stmt->execute([$id, $idEmpresa]);
        } catch (Exception $e) {
            error_log("Error al activar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar permanentemente un cliente
     */
    public function eliminar(int $id, int $idEmpresa): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM clientes 
                WHERE id = ? AND idEmpresa = ?
            ");
            return $stmt->execute([$id, $idEmpresa]);
        } catch (Exception $e) {
            error_log("Error al eliminar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar clientes por nombre o teléfono
     */
    public function buscar(int $idEmpresa, string $termino): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM clientes 
                WHERE idEmpresa = ?
                AND activo = 1
                AND (
                    nombre_cliente LIKE ?
                    OR telefono_usuario LIKE ?
                    OR email LIKE ?
                )
                ORDER BY nombre_cliente ASC
            ");
            
            $terminoBusqueda = "%{$termino}%";
            $stmt->execute([$idEmpresa, $terminoBusqueda, $terminoBusqueda, $terminoBusqueda]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al buscar clientes: " . $e->getMessage());
            return [];
        }
    }
}
