<?php

namespace App\Models;

use App\Database\Database;
use PDO;
use Exception;

/**
 * Modelo para manejar los horarios de las empresas
 */
class Horario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener horarios de una empresa
     */
    public function obtenerPorEmpresa(int $idEmpresa): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM horarios 
                WHERE idEmpresa = ?
            ");
            $stmt->execute([$idEmpresa]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error al obtener horarios: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear o actualizar horarios de una empresa
     */
    public function guardar(int $idEmpresa, array $horarios): bool
    {
        try {
            // Verificar si ya existe
            $existe = $this->obtenerPorEmpresa($idEmpresa);
            
            if ($existe) {
                // Actualizar
                $stmt = $this->db->prepare("
                    UPDATE horarios SET
                        lunes_apertura = :lunes_apertura,
                        lunes_cierre = :lunes_cierre,
                        martes_apertura = :martes_apertura,
                        martes_cierre = :martes_cierre,
                        miercoles_apertura = :miercoles_apertura,
                        miercoles_cierre = :miercoles_cierre,
                        jueves_apertura = :jueves_apertura,
                        jueves_cierre = :jueves_cierre,
                        viernes_apertura = :viernes_apertura,
                        viernes_cierre = :viernes_cierre,
                        sabado_apertura = :sabado_apertura,
                        sabado_cierre = :sabado_cierre,
                        domingo_apertura = :domingo_apertura,
                        domingo_cierre = :domingo_cierre,
                        tiempo_atencion = :tiempo_atencion
                    WHERE idEmpresa = :idEmpresa
                ");
            } else {
                // Insertar
                $stmt = $this->db->prepare("
                    INSERT INTO horarios (
                        idEmpresa, lunes_apertura, lunes_cierre,
                        martes_apertura, martes_cierre,
                        miercoles_apertura, miercoles_cierre,
                        jueves_apertura, jueves_cierre,
                        viernes_apertura, viernes_cierre,
                        sabado_apertura, sabado_cierre,
                        domingo_apertura, domingo_cierre,
                        tiempo_atencion
                    ) VALUES (
                        :idEmpresa, :lunes_apertura, :lunes_cierre,
                        :martes_apertura, :martes_cierre,
                        :miercoles_apertura, :miercoles_cierre,
                        :jueves_apertura, :jueves_cierre,
                        :viernes_apertura, :viernes_cierre,
                        :sabado_apertura, :sabado_cierre,
                        :domingo_apertura, :domingo_cierre,
                        :tiempo_atencion
                    )
                ");
            }
            
            $horarios['idEmpresa'] = $idEmpresa;
            return $stmt->execute($horarios);
            
        } catch (Exception $e) {
            error_log("Error al guardar horarios: " . $e->getMessage());
            return false;
        }
    }
}
